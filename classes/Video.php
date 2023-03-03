<?php

namespace Chassit\Repeat;

class Video
{
    public string $name;
    public string $id;
    public ?float $start;
    public ?float $end;
    public int $playtime;
    public ?bool $NSFC;

    public function __construct(string $id, string $name, ?float $start = null, ?float $end = null, int $playtime = 0, bool $NSFC = false)
    {
        $this->name = $name;
        $this->id = $id;
        $this->start = $start;
        $this->end = $end;
        $this->playtime = $playtime;
        $this->NSFC = $NSFC;
    }
}
