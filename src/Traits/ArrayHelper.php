<?php

namespace Newerton\Yii2Boleto\Traits;

use ArrayAccess;
use Closure;

trait ArrayHelper
{

    /**
     * Assign high numeric IDs to a config item to force appending.
     *
     * @param array $array
     * @return  array
     */
    function append_config(array $array)
    {
        $start = 9999;

        foreach ($array as $key => $value) {
            if (is_numeric($key)) {
                $start++;
                $array[$start] = $this->array_pull($array, $key);
            }
        }

        return $array;
    }

    /**
     * Add an element to an array using "dot" notation if it doesn't exist.
     *
     * @param array $array
     * @param string $key
     * @param mixed $value
     * @return array
     */
    function array_add($array, $key, $value)
    {
        if (is_null($this->array_get($array, $key))) {
            $this->array_set($array, $key, $value);
        }

        return $array;
    }


    /**
     * Collapse an array of arrays into a single array.
     *
     * @param array $array
     * @return array
     */
    function array_collapse($array)
    {
        $results = [];

        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();
            } elseif (!is_array($values)) {
                continue;
            }
            $results = array_merge($results, $values);
        }

        return $results;
    }


    /**
     * Divide an array into two arrays. One with keys and the other with values.
     *
     * @param array $array
     * @return array
     */
    function array_divide($array)
    {
        return [array_keys($array), array_values($array)];
    }


    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param array $array
     * @param string $prepend
     * @return array
     */
    function array_dot($array, $prepend = '')
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, $this->array_dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }


    /**
     * Get all of the given array except for a specified array of keys.
     *
     * @param array $array
     * @param array|string $keys
     * @return array
     */
    function array_except($array, $keys)
    {
        $this->array_forget($array, $keys);
        return $array;
    }


    /**
     * Determine if the given key exists in the provided array.
     *
     * @param ArrayAccess|array $array
     * @param string|int $key
     * @return bool
     */
    function array_exists($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }


    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param array $array
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    function array_first($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($array)) {
                return $this->value($default);
            }
            foreach ($array as $item) {
                return $item;
            }
        }

        foreach ($array as $key => $value) {
            if (call_user_func($callback, $value, $key)) {
                return $value;
            }
        }

        return $this->value($default);
    }


    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param array $array
     * @param int $depth
     * @return array
     */
    function array_flatten($array, $depth = INF)
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $result = array_merge($result, array_values($item));
            } else {
                $result = array_merge($result, $this->array_flatten($item, $depth - 1));
            }
        }

        return $result;
    }


    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param array $array
     * @param array|string $keys
     * @return void
     */
    function array_forget(&$array, $keys)
    {
        $original = &$array;
        $keys = (array)$keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if ($this->array_exists($array, $key)) {
                unset($array[$key]);
                continue;
            }
            $parts = explode('.', $key);
            // clean up before each pass
            $array = &$original;
            while (count($parts) > 1) {
                $part = array_shift($parts);
                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }


    /**
     * Get an item from an array using "dot" notation.
     *
     * @param ArrayAccess|array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function array_get($array, $key, $default = null)
    {
        if (!$this->array_accessible($array)) {
            return $this->value($default);
        }
        if (is_null($key)) {
            return $array;
        }
        if ($this->array_exists($array, $key)) {
            return $array[$key];
        }
        if (strpos($key, '.') === false) {
            return isset($array[$key]) ? $array[$key] : $this->value($default);
        }
        foreach (explode('.', $key) as $segment) {
            if ($this->array_accessible($array) && $this->array_exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return $this->value($default);
            }
        }

        return $array;
    }


    /**
     * Check if an item or items exist in an array using "dot" notation.
     *
     * @param ArrayAccess|array $array
     * @param string|array $keys
     * @return bool
     */
    function array_has($array, $keys)
    {
        if (is_null($keys)) {
            return false;
        }
        $keys = (array)$keys;
        if (!$array) {
            return false;
        }
        if ($keys === []) {
            return false;
        }
        foreach ($keys as $key) {
            $subKeyArray = $array;
            if ($this->array_exists($array, $key)) {
                continue;
            }
            foreach (explode('.', $key) as $segment) {
                if ($this->array_accessible($subKeyArray) && $this->array_exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param array $array
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    function array_last($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            return empty($array) ? $this->value($default) : end($array);
        }

        return $this->first(array_reverse($array, true), $callback, $default);
    }


    /**
     * Get a subset of the items from the given array.
     *
     * @param array $array
     * @param array|string $keys
     * @return array
     */
    function array_only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array)$keys));
    }


    /**
     * Pluck an array of values from an array.
     *
     * @param array $array
     * @param string|array $value
     * @param string|array|null $key
     * @return array
     */
    function array_pluck($array, $value, $key = null)
    {
        $results = [];
        list($value, $key) = $this->explodePluckParameters($value, $key);
        foreach ($array as $item) {
            $itemValue = $this->data_get($item, $value);
            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = $this->data_get($item, $key);
                if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                    $itemKey = (string)$itemKey;
                }
                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }


    /**
     * Push an item onto the beginning of an array.
     *
     * @param array $array
     * @param mixed $value
     * @param mixed $key
     * @return array
     */
    function array_prepend($array, $value, $key = null)
    {
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }

        return $array;
    }


    /**
     * Get a value from the array, and remove it.
     *
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function array_pull(&$array, $key, $default = null)
    {
        $value = $this->array_get($array, $key, $default);
        $this->array_forget($array, $key);

        return $value;
    }


    /**
     * Get a random value from an array.
     *
     * @param array $array
     * @param int|null $num
     * @return mixed
     */
    function array_random($array, $num = null)
    {
        $requested = is_null($num) ? 1 : $num;
        $count = count($array);
        if ($requested > $count) {
            throw new InvalidArgumentException(
                "You requested {$requested} items, but there are only {$count} items available."
            );
        }
        if (is_null($num)) {
            return $array[array_rand($array)];
        }
        if ((int)$num === 0) {
            return [];
        }
        $keys = array_rand($array, $num);
        $results = [];
        foreach ((array)$keys as $key) {
            $results[] = $array[$key];
        }

        return $results;
    }


    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param array $array
     * @param string $key
     * @param mixed $value
     * @return array
     */
    function array_set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }
        $keys = explode('.', $key);
        while (count($keys) > 1) {
            $key = array_shift($keys);
            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }
        $array[array_shift($keys)] = $value;

        return $array;
    }


    /**
     * Filter the array using the given callback.
     *
     * @param array $array
     * @param callable $callback
     * @return array
     */
    function array_where($array, callable $callback)
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }


    /**
     * If the given value is not an array, wrap it in one.
     *
     * @param mixed $value
     * @return array
     */
    function array_wrap($value)
    {
        if (is_null($value)) {
            return [];
        }

        return !is_array($value) ? [$value] : $value;
    }


    /**
     * Determine if the given value is "blank".
     *
     * @param mixed $value
     * @return bool
     */
    function blank($value)
    {
        if (is_null($value)) {
            return true;
        }
        if (is_string($value)) {
            return trim($value) === '';
        }
        if (is_numeric($value) || is_bool($value)) {
            return false;
        }
        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }


    /**
     * Fill in data where it's missing.
     *
     * @param mixed $target
     * @param string|array $key
     * @param mixed $value
     * @return mixed
     */
    function data_fill(&$target, $key, $value)
    {
        return $this->data_set($target, $key, $value, false);
    }


    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param mixed $target
     * @param string|array $key
     * @param mixed $default
     * @return mixed
     */
    function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }
        $key = is_array($key) ? $key : explode('.', $key);
        while (!is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (!is_array($target)) {
                    return $this->value($default);
                }
                $result = $this->array_pluck($target, $key);

                return in_array('*', $key) ? $this->array_collapse($result) : $result;
            }
            if ($this->array_accessible($target) && $this->array_exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return $this->value($default);
            }
        }

        return $target;
    }


    /**
     * Set an item on an array or object using dot notation.
     *
     * @param mixed $target
     * @param string|array $key
     * @param mixed $value
     * @param bool $overwrite
     * @return mixed
     */
    function data_set(&$target, $key, $value, $overwrite = true)
    {
        $segments = is_array($key) ? $key : explode('.', $key);
        if (($segment = array_shift($segments)) === '*') {
            if (!$this->array_accessible($target)) {
                $target = [];
            }
            if ($segments) {
                foreach ($target as &$inner) {
                    $this->data_set($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif ($this->array_accessible($target)) {
            if ($segments) {
                if (!$this->array_exists($target, $segment)) {
                    $target[$segment] = [];
                }
                $this->data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || !$this->array_exists($target, $segment)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (!isset($target->{$segment})) {
                    $target->{$segment} = [];
                }
                $this->data_set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || !isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];
            if ($segments) {
                $this->data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }

        return $target;
    }


    /**
     * Determine if a value is "filled".
     *
     * @param mixed $value
     * @return bool
     */
    function filled($value)
    {
        return !$this->blank($value);
    }


    /**
     * Get the first element of an array. Useful for method chaining.
     *
     * @param array $array
     * @return mixed
     */
    function head($array)
    {
        return reset($array);
    }


    /**
     * Get the last element from an array.
     *
     * @param array $array
     * @return mixed
     */
    function last($array)
    {
        return end($array);
    }


    /**
     * Determines if an array is associative.
     *
     * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
     *
     * @param array $array
     * @return bool
     */
    function isAssoc(array $array)
    {
        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }


    /**
     * Explode the "value" and "key" arguments passed to "pluck".
     *
     * @param string|array $value
     * @param string|array|null $key
     * @return array
     */
    function explodePluckParameters($value, $key)
    {
        $value = is_string($value) ? explode('.', $value) : $value;
        $key = is_null($key) || is_array($key) ? $key : explode('.', $key);

        return [$value, $key];
    }


    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param array $array
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    function first($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($array)) {
                return $this->value($default);
            }
            foreach ($array as $item) {
                return $item;
            }
        }
        foreach ($array as $key => $value) {
            if (call_user_func($callback, $value, $key)) {
                return $value;
            }
        }

        return $this->value($default);
    }


    /**
     * Transform the given value if it is present.
     *
     * @param mixed $value
     * @param callable $callback
     * @param mixed $default
     * @return mixed|null
     */
    function transform($value, callable $callback, $default = null)
    {
        if ($this->filled($value)) {
            return $callback($value);
        }
        if (is_callable($default)) {
            return $default($value);
        }

        return $default;
    }


    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }


    /**
     * Return the given value, optionally passed through the given callback.
     *
     * @param mixed $value
     * @param callable|null $callback
     * @return mixed
     */
    function with($value, callable $callback = null)
    {
        return is_null($callback) ? $value : $callback($value);
    }


    /**
     * Determine whether the given value is array accessible.
     *
     * @param mixed $value
     * @return bool
     */
    function array_accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }


    /**
     * Sort the array by the given callback or attribute name.
     *
     * @param array $array
     * @param callable|string|null $callback
     * @return array
     */
    function array_sort($array, $callback = null)
    {
        if ($this->isAssoc($array)) {
            ksort($array);
        } elseif (!is_null($callback)) {
            $callback ? krsort($array, SORT_REGULAR) : ksort($array, SORT_REGULAR);
        } else {
            sort($array);
        }

        return $array;
    }


    /**
     * Recursively sort an array by keys and values.
     *
     * @param array $array
     * @return array
     */
    function array_sort_recursive($array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = $this->array_sort_recursive($value);
            }
        }

        if ($this->isAssoc($array)) {
            ksort($array);
        } else {
            sort($array);
        }

        return $array;
    }

}
