<?php

declare(strict_types=1);

namespace Diviky\Bright\Concerns;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Arr;

trait Responsable
{
    /**
     * Get the view.
     *
     * @param string $route
     * @param mixed  $data
     * @param string $layout
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    protected function getView($route, $data = [], $layout = null)
    {
        $layout = $layout ?: 'index';
        $data['component'] = $route;

        $factory = app(ViewFactory::class);

        return $factory->make($layout, $data);
    }

    /**
     * Get the route name from action.
     *
     * @param string $action
     */
    protected function getRouteFromAction($action): string
    {
        $method = $this->getMethod($action);
        $component = $this->getNamespace($action);

        return \strtolower($component . '.' . $method);
    }

    /**
     * Get the method name of the route action.
     *
     * @param mixed $action
     */
    protected function getMethod($action): string
    {
        return Arr::last(\explode('@', $action));
    }

    /**
     * Get the namespace of the action.
     *
     * @param string $action
     */
    protected function getNamespace($action): ?string
    {
        if (false === \strpos($action, '@')) {
            return null;
        }

        $action = \explode('@', $action);
        $controller = \explode('\\', $action[0]);
        $controller = $controller[\count($controller) - 2];

        return \strtolower($controller);
    }

    /**
     * Get the view path.
     *
     * @param string $action
     */
    protected function getViewPath($action): ?string
    {
        if (false === \strpos($action, '@')) {
            return null;
        }

        $action = \explode('@', $action);
        $action = \explode('\\', $action[0]);
        $action = \array_filter($action);
        \array_pop($action);
        $path = \implode(DIRECTORY_SEPARATOR, \array_slice($action, 1));

        return app_path($path . '/views');
    }

    /**
     * Get redirect route from response.
     *
     * @param array  $response
     * @param string $keyword
     *
     * @return null|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    protected function getNextRedirect($response = [], $keyword = 'next')
    {
        $next = $response[$keyword];
        if (!isset($next)) {
            return $response;
        }

        unset($response[$keyword]);

        $redirect = null;
        if (\is_string($next)) {
            if ('/' == \substr($next, 0, 1)) {
                $redirect = redirect($next);
            } elseif ('back' == $next) {
                $redirect = redirect()->back();
            } elseif ('intended' == $next) {
                $redirect = redirect()->intended('/');
            } else {
                $redirect = redirect()->route($next);
            }
        } elseif (\is_array($next)) {
            if ($next['back']) {
                $redirect = redirect()->back();
            } elseif ($next['path']) {
                $redirect = redirect($next['path']);
            } elseif ($next['next']) {
                $redirect = redirect()->route($next['route']);
            } elseif ($next['intended']) {
                $redirect = redirect()->intended($next['intended']);
            }
        } elseif ($next instanceof \Closure) {
            $redirect = $next();
        }

        if (isset($redirect)) {
            foreach ($response as $key => $value) {
                $redirect = $redirect->with($key, $value);
            }
        }

        return $redirect;
    }

    /**
     * Get the view locations from controller.
     *
     * @param mixed       $controller
     * @param null|string $action
     *
     * @return array
     */
    protected function getViewsFrom($controller, $action = null)
    {
        if (\method_exists($controller, 'getViewsFrom')) {
            $paths = $controller->getViewsFrom();
            $paths = !\is_array($paths) ? [$paths] : $paths;
            foreach ($paths as $key => $path) {
                $paths[$key] = $path . '/views/';
            }

            return $paths;
        }

        if (\method_exists($controller, 'loadViewsFrom')) {
            $paths = $controller->loadViewsFrom();
            $paths = !\is_array($paths) ? [$paths] : $paths;
            foreach ($paths as $key => $path) {
                $paths[$key] = $path . '/views/';
            }

            return $paths;
        }

        if ($action) {
            return [$this->getViewPath($action)];
        }

        return [];
    }
}
