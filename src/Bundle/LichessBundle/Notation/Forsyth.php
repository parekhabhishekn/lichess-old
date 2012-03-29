<?php

namespace Bundle\LichessBundle\Notation;
use Bundle\LichessBundle\Document\Game;
use Bundle\LichessBundle\Document\Piece;
use Bundle\LichessBundle\Chess\Board;
use Bundle\LichessBundle\Chess\PieceFilter;

class Forsyth
{
    /**
     * Create and position pieces of the game for the forsyth string
     *
     * @param Game $game
     * @param string $forsyth
     * @return Game $game
     */
    public static function import(Game $game, $forsyth)
    {
        static $classes = array('p' => 'Pawn', 'r' => 'Rook', 'n' => 'Knight', 'b' => 'Bishop', 'q' => 'Queen', 'k' => 'King');
        $x = 1;
        $y = 8;
        $board = $game->getBoard();
        $forsyth = str_replace('/', '', preg_replace('#\s*([\w\d/]+)\s.+#i', '$1', $forsyth));
        $pieces = array('white' => array(), 'black' => array());

        for($itForsyth = 0, $forsythLen = strlen($forsyth); $itForsyth < $forsythLen; $itForsyth++) {
            $letter = $forsyth{$itForsyth};

            if (is_numeric($letter)) {
                $x += intval($letter);
            } else {
                $color = ctype_lower($letter) ? 'black' : 'white';
                $pieces[$color][] = new Piece($x, $y, $classes[strtolower($letter)]);
                ++$x;
            }

            if($x > 8) {
                $x = 1;
                --$y;
            }
        }

        foreach ($game->getPlayers() as $player) {
            $player->setPieces($pieces[$player->getColor()]);
        }
        $game->ensureDependencies();
    }

    protected static function pieceToForsyth(Piece $piece)
    {
        static $reverseClasses = array('Pawn' => 'p', 'Rook' => 'r', 'Knight' => 'n', 'Bishop' => 'b', 'Queen' => 'q', 'King' => 'k');

        $notation = $reverseClasses[$piece->getClass()];

        if('white' === $piece->getColor()) {
            $notation = strtoupper($notation);
        }

        return $notation;
    }
}
