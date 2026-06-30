<?php

declare(strict_types=1);

namespace App;

use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use Doctrine\DBAL\Types\Exception\TypesException;
use Doctrine\DBAL\Types\Type;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Yokai\DoctrineValueObject\Doctrine\Types;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const array DOCTRINE_VALUE_OBJECTS = [
        'name' => Name::class,
        'email' => Email::class,
    ];

    /**
     * @throws TypesException
     */
    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);
        new Types(self::DOCTRINE_VALUE_OBJECTS)->register(Type::getTypeRegistry());
    }

    /**
     * @return list<string>
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }
}
