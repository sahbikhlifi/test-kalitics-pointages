<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Project;
use DateTime;
use PHPUnit\Framework\TestCase;

class ProjectTest extends TestCase
{
    public static function provideDates(): iterable
    {
        yield 'avant ouverture' => ['2024-01-09', '2024-01-10', '2024-01-20', false];
        yield 'jour d\'ouverture' => ['2024-01-10', '2024-01-10', '2024-01-20', true];
        yield 'pendant' => ['2024-01-15', '2024-01-10', '2024-01-20', true];
        yield 'jour de clôture' => ['2024-01-20', '2024-01-10', '2024-01-20', true];
        yield 'après clôture' => ['2024-01-21', '2024-01-10', '2024-01-20', false];
        yield 'sans date de fin' => ['2030-01-01', '2024-01-10', null, true];
    }

    /**
     * @dataProvider provideDates
     */
    public function testIsOpenOn(string $date, string $start, ?string $end, bool $expected): void
    {
        $project = new Project();
        $project->setDateStart(new DateTime($start));
        $project->setDateEnd($end === null ? null : new DateTime($end));

        self::assertSame($expected, $project->isOpenOn(new DateTime("$date 18:30")));
    }
}
