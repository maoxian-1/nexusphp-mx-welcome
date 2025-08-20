<?php
namespace NexusPlugin\MxWelcome;

class Welcome
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'mx-welcome';
    }
}
