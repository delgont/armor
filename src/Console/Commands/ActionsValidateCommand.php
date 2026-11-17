<?php

namespace Delgont\Armor\Console\Commands;

use Delgont\Armor\Armor;
use Delgont\Armor\Support\ActionRegistrars;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;

class ActionsValidateCommand extends Command
{
    protected $signature = 'armor:actions:validate {--strict : Fail on warnings too}';
    protected $description = 'Validate action registrars (code only, no database needed)';

    private int $fails = 0;
    private int $warns = 0;

    public function handle(): int
    {
        [$instances, $absent, $invalid] = ActionRegistrars::resolve();

        if (Armor::getActionRegistrars() === []) {
            $this->warn('No action registrars are registered.');
            return self::SUCCESS;
        }

        $this->newLine();

        foreach ($absent as $class) {
            $this->bad(OutputFormatter::escape($class).' does not exist');
        }
        foreach ($invalid as $class) {
            $this->bad(OutputFormatter::escape($class).' does not extend SystemActionRegistrar');
        }

        $seen = [];      // action key => registrars that declare it
        $groups = [];    // group name => registrars that use it

        foreach ($instances as $class => $registrar) {
            $this->line('  <options=bold>'.OutputFormatter::escape($class).'</>');

            $before = [$this->fails, $this->warns];
            $keys = ActionRegistrars::keys($registrar);

            // Anything that is not a string breaks the sync, because the value is used as the action key.
            foreach ($registrar->getActions() as $name => $value) {
                if (! is_string($value)) {
                    $this->bad("constant {$name} is not a string and would break the sync", 4);
                }
            }

            if ($keys === []) {
                $this->caution('declares no actions', 4);
            }

            foreach (array_keys(array_filter(array_count_values($keys), fn ($n) => $n > 1)) as $dup) {
                $this->bad("action '{$dup}' is declared more than once", 4);
            }

            foreach ($keys as $key) {
                if (! preg_match('/^[a-z][a-z0-9]*(_[a-z0-9]+)*$/', $key)) {
                    $this->caution("action '{$key}' is not snake_case", 4);
                }
                $seen[$key][] = class_basename($class);
            }

            $described = $registrar->getDescriptions();

            if ($missing = array_diff($keys, array_keys($described))) {
                $this->caution(count($missing).' action(s) have no description: '.implode(', ', array_slice($missing, 0, 3)).(count($missing) > 3 ? '...' : ''), 4);
            }

            if ($unknown = array_diff(array_keys($described), $keys)) {
                $this->caution('descriptions for actions that do not exist: '.implode(', ', $unknown), 4);
            }

            if ($unknown = array_diff($registrar->getUndoable(), $keys)) {
                $this->caution('undoable lists actions that do not exist: '.implode(', ', $unknown), 4);
            }

            $groups[ActionRegistrars::groupName($registrar)][] = class_basename($class);

            if ([$this->fails, $this->warns] === $before) {
                $this->line('    <fg=green>OK</>    '.count($keys).' action(s)');
            }
        }

        $this->newLine();
        $this->line('  <options=bold>Across registrars</>');
        $clean = true;

        // The same key in two groups makes audit_logs.action ambiguous.
        foreach (array_filter($seen, fn ($c) => count($c) > 1) as $key => $classes) {
            $clean = false;
            $this->caution("action '{$key}' is declared by ".implode(', ', $classes).', so audit entries for it are ambiguous', 4);
        }

        foreach (array_filter($groups, fn ($c) => count($c) > 1) as $name => $classes) {
            $clean = false;
            $this->caution("registrars ".implode(', ', $classes)." share the group name '{$name}'", 4);
        }

        $clean ? $this->line('    <fg=green>OK</>    no duplicate action keys or group names') : null;

        $this->newLine();
        $this->line("  {$this->fails} failure(s), {$this->warns} warning(s)");
        $this->newLine();

        return ($this->fails > 0 || ($this->option('strict') && $this->warns > 0)) ? self::FAILURE : self::SUCCESS;
    }

    private function caution(string $text, int $indent = 2): void
    {
        $this->warns++;
        $this->line(str_repeat(' ', $indent).'<fg=yellow>WARN</>  '.$text);
    }

    private function bad(string $text, int $indent = 2): void
    {
        $this->fails++;
        $this->line(str_repeat(' ', $indent).'<fg=red>FAIL</>  '.$text);
    }
}