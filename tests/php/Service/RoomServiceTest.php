<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Talk\Tests\php\Service;

use InvalidArgumentException;
use OCA\Talk\Exceptions\RoomNotFoundException;
use OCA\Talk\Manager;
use OCA\Talk\Participant;
use OCA\Talk\Room;
use OCA\Talk\Service\ParticipantService;
use OCA\Talk\Service\RoomService;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class RoomServiceTest extends TestCase {

	/** @var Manager|MockObject */
	protected $manager;
	/** @var ParticipantService|MockObject */
	protected $participantService;
	/** @var RoomService */
	private $service;


	public function setUp(): void {
		parent::setUp();

		$this->manager = $this->createMock(Manager::class);
		$this->participantService = $this->createMock(ParticipantService::class);
		$this->service = new RoomService(
			$this->manager,
			$this->participantService
		);
	}

	public function testCreateOneToOneConversationWithSameUser(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('uid');

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('invalid_invitee');
		$this->service->createOneToOneConversation($user, $user);
	}

	public function testCreateOneToOneConversationAlreadyExists(): void {
		$user1 = $this->createMock(IUser::class);
		$user1->method('getUID')
			->willReturn('uid1');
		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')
			->willReturn('uid2');

		$room = $this->createMock(Room::class);
		$this->participantService->expects($this->once())
			->method('ensureOneToOneRoomIsFilled')
			->with($room);

		$this->manager->expects($this->once())
			->method('getOne2OneRoom')
			->with('uid1', 'uid2')
			->willReturn($room);

		$this->assertSame($room, $this->service->createOneToOneConversation($user1, $user2));
	}

	public function testCreateOneToOneConversationCreated(): void {
		$user1 = $this->createMock(IUser::class);
		$user1->method('getUID')
			->willReturn('uid1');
		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')
			->willReturn('uid2');

		$room = $this->createMock(Room::class);
		$this->participantService->expects($this->once())
			->method('addUsers')
			->with($room, [[
				'actorType' => 'users',
				'actorId' => 'uid1',
				'participantType' => Participant::OWNER,
			], [
				'actorType' => 'users',
				'actorId' => 'uid2',
				'participantType' => Participant::OWNER,
			]]);

		$this->participantService->expects($this->never())
			->method('ensureOneToOneRoomIsFilled')
			->with($room);

		$this->manager->expects($this->once())
			->method('getOne2OneRoom')
			->with('uid1', 'uid2')
			->willThrowException(new RoomNotFoundException());

		$this->manager->expects($this->once())
			->method('createRoom')
			->with(Room::ONE_TO_ONE_CALL)
			->willReturn($room);

		$this->assertSame($room, $this->service->createOneToOneConversation($user1, $user2));
	}

	public function dataCreateConversationInvalidNames(): array {
		return [
			[''],
			['        '],
			[str_repeat('a', 256)],
		];
	}

	/**
	 * @dataProvider dataCreateConversationInvalidNames
	 * @param string $name
	 */
	public function testCreateConversationInvalidNames(string $name): void {
		$this->manager->expects($this->never())
			->method('createRoom');

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('name');
		$this->service->createConversation(Room::GROUP_CALL, $name);
	}

	public function dataCreateConversationInvalidTypes(): array {
		return [
			[Room::ONE_TO_ONE_CALL],
			[Room::UNKNOWN_CALL],
			[5],
		];
	}

	/**
	 * @dataProvider dataCreateConversationInvalidTypes
	 * @param int $type
	 */
	public function testCreateConversationInvalidTypes(int $type): void {
		$this->manager->expects($this->never())
			->method('createRoom');

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('type');
		$this->service->createConversation($type, 'abc');
	}

	public function dataCreateConversationInvalidObjects(): array {
		return [
			[str_repeat('a', 65), 'a', 'object_type'],
			['a', str_repeat('a', 65), 'object_id'],
			['a', '', 'object'],
			['', 'b', 'object'],
		];
	}

	/**
	 * @dataProvider dataCreateConversationInvalidObjects
	 * @param string $type
	 * @param string $id
	 * @param string $exception
	 */
	public function testCreateConversationInvalidObjects(string $type, string $id, string $exception): void {
		$this->manager->expects($this->never())
			->method('createRoom');

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage($exception);
		$this->service->createConversation(Room::PUBLIC_CALL, 'a', null, $type, $id);
	}

	public function dataCreateConversation(): array {
		return [
			[Room::GROUP_CALL, 'Group conversation', 'admin', '', ''],
			[Room::PUBLIC_CALL, 'Public conversation', '', 'files', '123456'],
			[Room::CHANGELOG_CONVERSATION, 'Talk updates ✅', 'test1', 'changelog', 'conversation'],
		];
	}

	/**
	 * @dataProvider dataCreateConversation
	 * @param int $type
	 * @param string $name
	 * @param string $ownerId
	 * @param string $objectType
	 * @param string $objectId
	 */
	public function testCreateConversation(int $type, string $name, string $ownerId, string $objectType, string $objectId): void {
		$room = $this->createMock(Room::class);

		if ($ownerId !== '') {
			$owner = $this->createMock(IUser::class);
			$owner->method('getUID')
				->willReturn($ownerId);

			$this->participantService->expects($this->once())
				->method('addUsers')
				->with($room, [[
					'actorType' => 'users',
					'actorId' => $ownerId,
					'participantType' => Participant::OWNER,
				]]);
		} else {
			$owner = null;
			$this->participantService->expects($this->never())
				->method('addUsers');
		}

		$this->manager->expects($this->once())
			->method('createRoom')
			->with($type, $name, $objectType, $objectId)
			->willReturn($room);

		$this->assertSame($room, $this->service->createConversation($type, $name, $owner, $objectType, $objectId));
	}

	public function dataPrepareConversationName(): array {
		return [
			['', ''],
			['    ', ''],
			['A    ', 'A'],
			['    B', 'B'],
			['  C  ', 'C'],
			['A' . str_repeat(' ', 100) . 'B', 'A'],
			['A' . str_repeat(' ', 32) . 'B', 'A' . str_repeat(' ', 32) . 'B'],
			['Лорем ипсум долор сит амет, но антиопам алияуандо витуперата еам, мел те цонгуе хомеро адолесценс.', 'Лорем ипсум долор сит амет, но антиопам алияуандо витуперата еам'],
		];
	}

	/**
	 * @dataProvider dataPrepareConversationName
	 * @param string $input
	 * @param string $expected
	 */
	public function testPrepareConversationName(string $input, string $expected): void {
		$this->assertSame($expected, $this->service->prepareConversationName($input));
	}
}
