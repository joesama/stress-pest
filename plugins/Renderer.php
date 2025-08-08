<?php

namespace Joesama\StressPest;

use function Termwind\render;

trait Renderer
{
    public function renderHeader($header): void
    {
        render(<<<HTML
            <div class="flex mx-2 max-w-150 text-gray">
                <span>
                $header
                </span>
                <span class="flex-1 ml-1 content-repeat-[―]"></span>
            </div>
        HTML
        );
    }

    protected function twoColumn(mixed $first, mixed $second): void
    {
        render(<<<HTML
            <div class="flex max-w-150 mx-2">
                <span>
                    $first
                </span>
                <span class="flex-1 content-repeat-[.] text-gray ml-1"></span>
                <span class="ml-1">
                    $second
                </span>
            </div>
        HTML);
    }
}