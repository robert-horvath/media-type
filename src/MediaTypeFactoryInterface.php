<?php
namespace RHo\MediaType;

interface MediaTypeFactoryInterface
{

    function fromString(string $mediaTypes): MediaTypeFactoryInterface;

    /**
     *
     * @return MediaTypeInterface[]|NULL
     */
    function build(): ?array;
}