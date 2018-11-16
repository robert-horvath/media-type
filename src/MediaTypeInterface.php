<?php
namespace RHo\MediaType;

interface MediaTypeInterface
{

    function type(): string;

    function subType(): string;

    function structuredSyntaxSuffix(): ?string;

    function parameter(string $key): ?string;

    function parameterQ(): int;

    function compareTo(MediaTypeInterface $mediaType): int;

    function __toString(): string;
}