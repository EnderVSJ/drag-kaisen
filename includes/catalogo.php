<?php
declare(strict_types=1);

/**
 * Catálogo de personajes y maldiciones (mismos valores que script.js).
 * El servidor lo usa para NO confiar en lo que manda el navegador:
 * nombres, grados y HP salen de acá, y cada golpe se valida contra ATK/DEF.
 */
function catalogo_hechiceros(): array
{
    return [
        'p1'  => ['nombre' => 'Panda',        'grado' => 1, 'hp' => 120, 'atk' => 18, 'def' => 6,  'spd' => 12],
        'p2'  => ['nombre' => 'Nobara',       'grado' => 1, 'hp' => 110, 'atk' => 20, 'def' => 5,  'spd' => 14],
        'p3'  => ['nombre' => 'Toji',         'grado' => 1, 'hp' => 100, 'atk' => 22, 'def' => 4,  'spd' => 16],
        'p4'  => ['nombre' => 'Hakari',       'grado' => 1, 'hp' => 125, 'atk' => 16, 'def' => 7,  'spd' => 11],
        'p5'  => ['nombre' => 'Maki',         'grado' => 1, 'hp' => 130, 'atk' => 15, 'def' => 8,  'spd' => 10],
        'p6'  => ['nombre' => 'Gojo',         'grado' => 2, 'hp' => 180, 'atk' => 26, 'def' => 10, 'spd' => 13],
        'p7'  => ['nombre' => 'Yuji',         'grado' => 2, 'hp' => 150, 'atk' => 28, 'def' => 9,  'spd' => 14],
        'p8'  => ['nombre' => 'Geto',         'grado' => 2, 'hp' => 170, 'atk' => 24, 'def' => 12, 'spd' => 12],
        'p9'  => ['nombre' => 'Choso',        'grado' => 2, 'hp' => 155, 'atk' => 27, 'def' => 11, 'spd' => 13],
        'p10' => ['nombre' => 'Inumaki',      'grado' => 3, 'hp' => 155, 'atk' => 22, 'def' => 16, 'spd' => 15],
        'p11' => ['nombre' => 'Sukuna',       'grado' => 3, 'hp' => 170, 'atk' => 24, 'def' => 18, 'spd' => 14],
        'p12' => ['nombre' => 'Megumi',       'grado' => 3, 'hp' => 165, 'atk' => 28, 'def' => 17, 'spd' => 16],
        'p13' => ['nombre' => 'Nah, Id Swim', 'grado' => 3, 'hp' => 220, 'atk' => 32, 'def' => 20, 'spd' => 13],
    ];
}

function catalogo_enemigos(): array
{
    return [
        'e1'  => ['nombre' => 'Maldición 1',  'grado' => 1, 'hp' => 100, 'atk' => 14, 'def' => 4,  'spd' => 10],
        'e2'  => ['nombre' => 'Maldición 2',  'grado' => 1, 'hp' => 110, 'atk' => 16, 'def' => 5,  'spd' => 11],
        'e3'  => ['nombre' => 'Maldición 3',  'grado' => 1, 'hp' => 95,  'atk' => 18, 'def' => 3,  'spd' => 13],
        'e4'  => ['nombre' => 'Maldición 4',  'grado' => 1, 'hp' => 120, 'atk' => 12, 'def' => 6,  'spd' => 9],
        'e5'  => ['nombre' => 'Maldición 5',  'grado' => 2, 'hp' => 150, 'atk' => 24, 'def' => 9,  'spd' => 12],
        'e6'  => ['nombre' => 'Maldición 6',  'grado' => 2, 'hp' => 160, 'atk' => 22, 'def' => 10, 'spd' => 11],
        'e7'  => ['nombre' => 'Maldición 7',  'grado' => 2, 'hp' => 155, 'atk' => 26, 'def' => 8,  'spd' => 13],
        'e8'  => ['nombre' => 'Maldición 8',  'grado' => 2, 'hp' => 200, 'atk' => 20, 'def' => 12, 'spd' => 10],
        'e9'  => ['nombre' => 'Maldición 9',  'grado' => 3, 'hp' => 210, 'atk' => 34, 'def' => 15, 'spd' => 14],
        'e10' => ['nombre' => 'Maldición 10', 'grado' => 3, 'hp' => 220, 'atk' => 32, 'def' => 18, 'spd' => 13],
    ];
}

/** Rango de daño posible de un golpe (misma fórmula que computeDamage en script.js). */
function rango_dano(array $atacante, array $defensor): array
{
    $base = max(1, $atacante['atk'] - intdiv($defensor['def'], 2));
    return [
        max(1, (int)floor($base * 0.9)),
        max(1, (int)ceil($base * 1.1)),
    ];
}
