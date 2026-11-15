<?php

namespace Delgont\Armor\Console\Commands;

use Delgont\Armor\Support\PermissionCache;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class CacheCheckCommand extends Command
{
    protected $signature = 'armor:cache:check';
    protected $description = 'Verify that the configured permission cache store works';

    private bool $failed = false;

    public function handle(PermissionCache $cache): int
    {
        $store = $cache->store();
        $driver = class_basename($store->getStore());

        $this->line('Store:  <info>'.$driver.'</info> (armor.cache_store = '.config('armor.cache_store', 'file').')');
        $this->line('TTL:    '.$cache->ttl().' seconds');
        $this->newLine();

        if (in_array($driver, ['ArrayStore', 'NullStore'], true)) {
            $this->result('Store persists between requests', false, "{$driver} forgets everything when the request ends");
        } elseif ($driver === 'FileStore' && app()->environment('production')) {
            $this->warn('  FileStore is per server. A revoked permission can stay active on other servers. Use redis or memcached.');
        }

        $samples = [
            'string'  => 'armor',
            'integer' => 42,
            'boolean' => true,
            'array'   => ['a' => 1, 'b' => ['x', 'y']],
        ];

        foreach ($samples as $type => $value) {
            $key = 'armor:check:'.$type;

            try {
                $store->put($key, $value, 60);
                $back = $store->get($key);
                $store->forget($key);

                $this->result("Write and read {$type}", $back === $value, $back === $value ? '' : 'value came back different');
            } catch (Throwable $e) {
                $this->result("Write and read {$type}", false, $e->getMessage());
            }
        }

        try {
            $key = 'armor:check:token';
            $a = $store->rememberForever($key, fn () => Str::random(16));
            $b = $store->rememberForever($key, fn () => Str::random(16));
            $this->result('Token stays stable between reads', $a === $b);

            $store->forever($key, 'rotated');
            $this->result('Token can be rotated', $store->get($key) === 'rotated');
            $store->forget($key);
        } catch (Throwable $e) {
            $this->result('Token handling', false, $e->getMessage());
        }

        $this->newLine();
        $this->failed ? $this->error('Cache check failed.') : $this->info('Cache store is working.');

        return $this->failed ? self::FAILURE : self::SUCCESS;
    }

    private function result(string $label, bool $ok, string $detail = ''): void
    {
        $this->failed = $this->failed || ! $ok;
        $this->line(($ok ? '  <fg=green>PASS</>' : '  <fg=red>FAIL</>').'  '.$label.($detail ? " <fg=gray>({$detail})</>" : ''));
    }
}