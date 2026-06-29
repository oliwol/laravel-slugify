<?php

declare(strict_types=1);

namespace Oliwol\Slugify;

use Closure;

final class SlugConfig
{
    /**
     * @var string|array<int, string>|Closure
     */
    private string|array|Closure $from = '';

    private ?string $to = null;

    private ?string $separator = null;

    private ?int $maxLength = null;

    private bool $regenerateOnUpdate = true;

    private bool $routeBinding = false;

    public static function create(): self
    {
        return new self;
    }

    public static function fromAttribute(Slugify $attribute): self
    {
        $config = new self;
        $config->from = $attribute->from;
        $config->to = $attribute->to;
        $config->separator = $attribute->separator;
        $config->maxLength = $attribute->maxLength;
        $config->regenerateOnUpdate = $attribute->regenerateOnUpdate;
        $config->routeBinding = $attribute->routeBinding;

        return $config;
    }

    /**
     * @param  string|array<int, string>|Closure  $from
     */
    public function from(string|array|Closure $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function to(string $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function separator(string $separator): self
    {
        $this->separator = $separator;

        return $this;
    }

    public function maxLength(int $maxLength): self
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    public function regenerateOnUpdate(bool $regenerateOnUpdate = true): self
    {
        $this->regenerateOnUpdate = $regenerateOnUpdate;

        return $this;
    }

    public function routeBinding(bool $routeBinding = true): self
    {
        $this->routeBinding = $routeBinding;

        return $this;
    }

    /**
     * @return string|array<int, string>|Closure
     */
    public function getFrom(): string|array|Closure
    {
        return $this->from;
    }

    public function getTo(): ?string
    {
        return $this->to;
    }

    public function getSeparator(): ?string
    {
        return $this->separator;
    }

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    public function shouldRegenerateOnUpdate(): bool
    {
        return $this->regenerateOnUpdate;
    }

    public function usesRouteBinding(): bool
    {
        return $this->routeBinding;
    }
}
