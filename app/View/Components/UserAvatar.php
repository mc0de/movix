<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class UserAvatar extends Component
{
    /**
     * The identicon foreground color.
     */
    public string $color;

    /**
     * The identicon background color.
     */
    public string $background;

    /**
     * A left-right symmetric 5x5 grid of filled/empty cells.
     *
     * @var array<int, bool>
     */
    public array $cells;

    public function __construct(public string $name)
    {
        $hash = md5(strtolower(trim($name)));

        $hue              = (int) (hexdec(substr($hash, 0, 2)) / 255 * 360);
        $this->color      = "hsl({$hue} 100% 62%)";
        $this->background = "hsl({$hue} 30% 9%)";

        $this->cells = $this->buildCells($hash);
    }

    /**
     * Build a deterministic, left-right symmetric 5x5 identicon grid.
     *
     * @return array<int, bool>
     */
    protected function buildCells(string $hash): array
    {
        $bytes = array_map('hexdec', str_split($hash, 2));
        $cells = array_fill(0, 25, false);

        for ($row = 0; $row < 5; $row++) {
            for ($col = 0; $col < 3; $col++) {
                $filled                       = ($bytes[$row * 3 + $col] % 2) === 0;
                $cells[$row * 5 + $col]       = $filled;
                $cells[$row * 5 + (4 - $col)] = $filled;
            }
        }

        return $cells;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.user-avatar');
    }
}
