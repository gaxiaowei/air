<?php
namespace Air\Kernel\Routing;

class RouteGroup
{
    /**
     * @param $new
     * @param $old
     * @return array
     */
    public static function merge($new, $old)
    {
        if (isset($new['domain'])) {
            unset($old['domain']);
        }

        $new = [
            'namespace' => static::formatNamespace($new, $old),
            'prefix' => static::formatPrefix($new, $old)
        ];

        return array_merge_recursive(
            ['middleware' => (array)($old['middleware'] ?? [])],
            $new
        );
    }

    /**
     * @param $new
     * @param $old
     * @return null|string
     */
    private static function formatNamespace($new, $old)
    {
        if (isset($new['namespace'])) {
            return isset($old['namespace'])
                ? trim($old['namespace'], '\\').'\\'.trim($new['namespace'], '\\')
                : trim($new['namespace'], '\\');
        }

        return $old['namespace'] ?? null;
    }

    /**
     * @param $new
     * @param $old
     * @return null|string]
     */
    private static function formatPrefix($new, $old)
    {
        $old = $old['prefix'] ?? null;

        return isset($new['prefix']) ? trim($old, '/').'/'.trim($new['prefix'], '/') : $old;
    }
}