<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Application\Service\Chat\ChatService;
use App\Domain\Entity\ChatRoom;
use App\Domain\Entity\User;
use App\Domain\Repository\ChatRoomRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Infrastructure layer: DoctrineChatRoomRepository::findSupportRooms (joins chat_rooms+users+last message).
 */
final class ChatRoomRepositoryTest extends KernelTestCase
{
    public function testFindSupportRoomsListsRoomWithLastMessage(): void
    {
        // Arrange
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $suffix = uniqid();
        $ids = $container->get(IdGeneratorInterface::class);
        $clock = $container->get(ClockInterface::class);

        $user = User::create(new UserId($ids->generate()), new Name('Chat User'), new Email(sprintf('room_%s@example.com', $suffix)));
        $em->persist($user);
        $em->flush();

        $chat = $container->get(ChatService::class);
        $rooms = $container->get(ChatRoomRepositoryInterface::class);
        $users = $container->get(UserRepositoryInterface::class);

        $freshUser = $users->findByEmail(new Email(sprintf('room_%s@example.com', $suffix)));
        $room = ChatRoom::create($freshUser->id(), $clock, $ids);
        $rooms->save($room);
        $em->flush();

        $roomEntity = $rooms->findByUserId($freshUser->id());
        $chat->createMessage($roomEntity, $freshUser, 'Hello support!');

        // Act
        $dtos = $rooms->findSupportRooms();

        // Assert: created room is listed with last message joined
        $found = null;
        foreach ($dtos as $dto) {
            if ($dto->id === $roomEntity->id()->toString()) {
                $found = $dto;

                break;
            }
        }
        self::assertNotNull($found, 'Created room should be listed for support');
        self::assertNotNull($found->lastMessage);
        self::assertSame('Hello support!', $found->lastMessage->text);
    }
}
