<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Application\Command\Chat\OpenChatRoomCommand;
use App\Application\Command\Chat\SendChatMessageCommand;
use App\Application\CommandHandler\Chat\OpenChatRoomHandler;
use App\Application\CommandHandler\Chat\SendChatMessageHandler;
use App\Application\Service\Chat\ChatService;
use App\Domain\Entity\OutboxMessage;
use App\Domain\Repository\ChatRoomRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\ChatMessageId;
use App\Domain\ValueObject\Email;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Application layer: Chat handlers with real PostgreSQL.
 */
final class ChatHandlersTest extends KernelTestCase
{
    public function testOpenChatRoomCreatesRoomForUser(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $suffix = uniqid();
        $data = IntegrationFixture::createPublishedEventWithSeat(
            $em,
            $container->get(\App\Domain\Shared\ClockInterface::class),
            $container->get(\App\Domain\Shared\IdGeneratorInterface::class),
            $suffix,
        );
        $userId = $data['user']->rawId();

        $handler = $container->get(OpenChatRoomHandler::class);
        $handler(new OpenChatRoomCommand($userId));
        $em->clear();

        $room = $container->get(ChatRoomRepositoryInterface::class)->findByUserId(
            new \App\Domain\ValueObject\UserId($userId)
        );

        self::assertNotNull($room);
        self::assertNotNull($room->id());
    }

    public function testOpenChatRoomReturnsExistingRoomForSameUser(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $suffix = uniqid();
        $data = IntegrationFixture::createPublishedEventWithSeat(
            $em,
            $container->get(\App\Domain\Shared\ClockInterface::class),
            $container->get(\App\Domain\Shared\IdGeneratorInterface::class),
            $suffix,
        );
        $userId = $data['user']->rawId();

        $handler = $container->get(OpenChatRoomHandler::class);
        $handler(new OpenChatRoomCommand($userId));
        $handler(new OpenChatRoomCommand($userId));
        $em->clear();

        $room = $container->get(ChatRoomRepositoryInterface::class)->findByUserId(
            new \App\Domain\ValueObject\UserId($userId)
        );

        self::assertNotNull($room);
    }

    public function testSendChatMessageCreatesMessageInRoom(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $suffix = uniqid();
        $data = IntegrationFixture::createPublishedEventWithSeat(
            $em,
            $container->get(\App\Domain\Shared\ClockInterface::class),
            $container->get(\App\Domain\Shared\IdGeneratorInterface::class),
            $suffix,
        );
        $userId = $data['user']->rawId();

        $handler = $container->get(OpenChatRoomHandler::class);
        $handler(new OpenChatRoomCommand($userId));
        $em->clear();

        $room = $container->get(ChatRoomRepositoryInterface::class)->findByUserId(
            new \App\Domain\ValueObject\UserId($userId)
        );
        $roomId = $room->rawId();

        $messageHandler = $container->get(SendChatMessageHandler::class);
        $messageHandler(new SendChatMessageCommand($roomId, $userId, 'Hello support!'));
        $em->clear();

        $messages = $container->get(\App\Domain\Repository\ChatMessageRepositoryInterface::class)->findByRoomId(
            new \App\Domain\ValueObject\ChatRoomId($roomId)
        );

        self::assertCount(1, $messages);
        self::assertSame('Hello support!', $messages[0]->text()->toString());
    }

    public function testSendChatMessageFailsForUnknownRoom(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $handler = $container->get(SendChatMessageHandler::class);

        $this->expectException(\App\Domain\Exception\EntityNotFoundException::class);

        $handler(new SendChatMessageCommand('00000000-0000-4000-8000-000000000000', 'user-id', 'Test message'));
    }

    public function testSendChatMessageFailsForUnknownUser(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $suffix = uniqid();
        $data = IntegrationFixture::createPublishedEventWithSeat(
            $em,
            $container->get(\App\Domain\Shared\ClockInterface::class),
            $container->get(\App\Domain\Shared\IdGeneratorInterface::class),
            $suffix,
        );
        $userId = $data['user']->rawId();

        $handler = $container->get(OpenChatRoomHandler::class);
        $handler(new OpenChatRoomCommand($userId));
        $em->clear();

        $room = $container->get(ChatRoomRepositoryInterface::class)->findByUserId(
            new \App\Domain\ValueObject\UserId($userId)
        );
        $roomId = $room->rawId();

        $messageHandler = $container->get(SendChatMessageHandler::class);

        $this->expectException(\App\Domain\Exception\EntityNotFoundException::class);

        $messageHandler(new SendChatMessageCommand($roomId, \App\Tests\Unit\Domain\Support\FixedIdGenerator::uuid(2), 'Test message'));
    }
}