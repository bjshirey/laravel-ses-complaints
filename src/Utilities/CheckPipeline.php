<?php


namespace Oza75\LaravelSesComplaints\Utilities;


use Illuminate\Pipeline\Pipeline;
use Throwable;

class CheckPipeline extends Pipeline
{
    protected function carry()
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_callable($pipe)) {
                    // If the pipe is an instance of a Closure, we will just call it directly but
                    // otherwise we'll resolve the pipes out of the container and call it with
                    // the appropriate method and arguments, returning the results back out.
                    return $pipe($passable, $stack);
                } elseif (is_array($pipe)) {
                    // Added 2023-10-05 b/c our pipe is an array (maybe Laravel 5.4 thing???).
                    $name = $pipe['middleware'];
                    $parameters = $pipe['options'] ?? [];
                    $pipe = $this->getContainer()->make($name);

                } elseif (!is_object($pipe)) {
                    [$name, $parameters] = $this->parsePipeString($pipe);

                    // If the pipe is a string we will parse the string and resolve the class out
                    // of the dependency injection container. We can then build a callable and
                    // execute the pipe function giving in the parameters that are required.
                    $pipe = $this->getContainer()->make($name);

                    $parameters = array_merge([$passable, $stack], $parameters);
                } else {
                    // If the pipe is already an object we'll just make a callable and pass it to
                    // the pipe as-is. There is no need to do any extra parsing and formatting
                    // since the object we're given was already a fully instantiated object.
                    $parameters = [$passable, $stack];
                }

                $response = method_exists($pipe, $this->method)
                        ? $pipe->{$this->method}($passable, $stack, $parameters)
                        : $pipe($passable, $stack, $parameters);

                return $response instanceof Responsable
                            ? $response->toResponse($this->getContainer()->make(Request::class))
                            : $response;
            };
        };
    }
}
