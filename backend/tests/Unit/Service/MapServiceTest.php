<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\Map\CreateMapDTO;
use App\DTO\Map\UpdateMapDTO;
use App\Entity\Game;
use App\Entity\GameMap;
use App\Repository\GameMapRepository;
use App\Service\MapService;
use App\Service\MercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MapServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;

    private GameMapRepository&MockObject $mapRepository;

    private MercurePublisher&MockObject $mercurePublisher;

    private MapService $mapService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->mapRepository = $this->createMock(GameMapRepository::class);
        $this->mercurePublisher = $this->createMock(MercurePublisher::class);

        $this->mapService = new MapService(
            $this->entityManager,
            $this->mapRepository,
            $this->mercurePublisher,
        );
    }

    public function testCreateMapWithAllFields(): void
    {
        $game = $this->createMock(Game::class);

        $dto = new CreateMapDTO();
        $dto->name = 'Test Map';
        $dto->description = 'A test map';
        $dto->imageUrl = '/uploads/map.jpg';
        $dto->gridSize = 50;
        $dto->gridType = 'square';
        $dto->width = 25;
        $dto->height = 20;
        $dto->isActive = false;
        $dto->settings = ['fog' => true];

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(GameMap::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $map = $this->mapService->createMap($game, $dto);

        $this->assertInstanceOf(GameMap::class, $map);
        $this->assertEquals('Test Map', $map->getName());
        $this->assertEquals('A test map', $map->getDescription());
        $this->assertEquals(50, $map->getGridSize());
        $this->assertEquals('square', $map->getGridType());
        $this->assertEquals(25, $map->getWidth());
        $this->assertEquals(20, $map->getHeight());
        $this->assertFalse($map->isActive());
    }

    public function testCreateMapDeactivatesOtherMapsWhenActive(): void
    {
        $game = $this->createMock(Game::class);
        $existingMap = $this->createMock(GameMap::class);

        $dto = new CreateMapDTO();
        $dto->name = 'New Active Map';
        $dto->width = 20;
        $dto->height = 20;
        $dto->isActive = true;

        $this->mapRepository->expects($this->once())
            ->method('findMapsByGame')
            ->with($game, true)
            ->willReturn([$existingMap]);

        $existingMap->expects($this->once())
            ->method('deactivate');

        $this->entityManager->expects($this->exactly(2))
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $map = $this->mapService->createMap($game, $dto);

        $this->assertTrue($map->isActive());
    }

    public function testGetActiveMap(): void
    {
        $game = $this->createMock(Game::class);
        $activeMap = $this->createMock(GameMap::class);

        $this->mapRepository->expects($this->once())
            ->method('findActiveMapByGame')
            ->with($game)
            ->willReturn($activeMap);

        $result = $this->mapService->getActiveMap($game);

        $this->assertSame($activeMap, $result);
    }

    public function testGetActiveMapReturnsNull(): void
    {
        $game = $this->createMock(Game::class);

        $this->mapRepository->expects($this->once())
            ->method('findActiveMapByGame')
            ->with($game)
            ->willReturn(null);

        $result = $this->mapService->getActiveMap($game);

        $this->assertNull($result);
    }

    public function testUpdateMapWithAllFields(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn(1);

        $map = $this->createMock(GameMap::class);
        $map->method('getGame')->willReturn($game);
        $map->method('getId')->willReturn(1);

        $dto = new UpdateMapDTO();
        $dto->name = 'Updated Name';
        $dto->description = 'Updated description';
        $dto->imageUrl = '/new-image.jpg';
        $dto->gridSize = 60;
        $dto->gridType = 'hex';
        $dto->width = 30;
        $dto->height = 25;
        $dto->isActive = false;
        $dto->settings = ['grid' => false];

        $map->expects($this->once())->method('setName')->with('Updated Name');
        $map->expects($this->once())->method('setDescription')->with('Updated description');
        $map->expects($this->once())->method('setImageUrl')->with('/new-image.jpg');
        $map->expects($this->once())->method('setGridSize')->with(60);
        $map->expects($this->once())->method('setGridType')->with('hex');
        $map->expects($this->once())->method('setWidth')->with(30);
        $map->expects($this->once())->method('setHeight')->with(25);
        $map->expects($this->once())->method('setIsActive')->with(false);
        $map->expects($this->once())->method('setSettings')->with(['grid' => false]);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->mercurePublisher->expects($this->once())
            ->method('publishMapChange');

        $result = $this->mapService->updateMap($map, $dto);

        $this->assertSame($map, $result);
    }

    public function testUpdateMapWithPartialFields(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn(1);

        $map = $this->createMock(GameMap::class);
        $map->method('getGame')->willReturn($game);
        $map->method('getId')->willReturn(1);

        $dto = new UpdateMapDTO();
        $dto->name = 'Only Name Updated';

        $map->expects($this->once())->method('setName');
        $map->expects($this->never())->method('setDescription');
        $map->expects($this->never())->method('setWidth');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->mercurePublisher->expects($this->once())
            ->method('publishMapChange');

        $this->mapService->updateMap($map, $dto);
    }

    public function testUpdateMapActivationDeactivatesOthers(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn(1);

        $map = $this->createMock(GameMap::class);
        $map->method('getGame')->willReturn($game);
        $map->method('getId')->willReturn(1);
        $map->method('getName')->willReturn('Test Map');
        $map->method('getDescription')->willReturn('Test Description');
        $map->method('getImageUrl')->willReturn('/test.jpg');
        $map->method('getGridSize')->willReturn(50);
        $map->method('getGridType')->willReturn('square');
        $map->method('getWidth')->willReturn(20);
        $map->method('getHeight')->willReturn(20);
        $map->method('isActive')->willReturn(true);
        $map->method('getSettings')->willReturn([]);

        $otherMap = $this->createMock(GameMap::class);

        $dto = new UpdateMapDTO();
        $dto->isActive = true;

        $map->expects($this->once())
            ->method('setIsActive')
            ->with(true);

        $this->mapRepository->expects($this->once())
            ->method('findMapsByGame')
            ->with($game, true)
            ->willReturn([$otherMap]);

        $otherMap->expects($this->once())
            ->method('deactivate');

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($otherMap);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->mercurePublisher->expects($this->once())
            ->method('publishMapChange');

        $this->mapService->updateMap($map, $dto);
    }

    public function testActivateMap(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn(1);

        $map = $this->createMock(GameMap::class);
        $map->method('getGame')->willReturn($game);
        $map->method('getId')->willReturn(1);
        $map->method('getName')->willReturn('Test Map');
        $map->method('getDescription')->willReturn('Test Description');
        $map->method('getImageUrl')->willReturn('/test.jpg');
        $map->method('getGridSize')->willReturn(50);
        $map->method('getGridType')->willReturn('square');
        $map->method('getWidth')->willReturn(20);
        $map->method('getHeight')->willReturn(20);
        $map->method('isActive')->willReturn(true);
        $map->method('getSettings')->willReturn([]);

        $this->mapRepository->expects($this->once())
            ->method('activateMap')
            ->with($map);

        $this->mercurePublisher->expects($this->once())
            ->method('publishMapChange');

        $result = $this->mapService->activateMap($map);

        $this->assertSame($map, $result);
    }

    public function testDeleteMap(): void
    {
        $map = $this->createMock(GameMap::class);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($map);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->mapService->deleteMap($map);
    }

    public function testGetMapsByGame(): void
    {
        $game = $this->createMock(Game::class);
        $maps = [$this->createMock(GameMap::class), $this->createMock(GameMap::class)];

        $this->mapRepository->expects($this->once())
            ->method('findMapsByGame')
            ->with($game, false)
            ->willReturn($maps);

        $result = $this->mapService->getMapsByGame($game);

        $this->assertSame($maps, $result);
        $this->assertCount(2, $result);
    }

    public function testGetMapsByGameActiveOnly(): void
    {
        $game = $this->createMock(Game::class);
        $activeMaps = [$this->createMock(GameMap::class)];

        $this->mapRepository->expects($this->once())
            ->method('findMapsByGame')
            ->with($game, true)
            ->willReturn($activeMaps);

        $result = $this->mapService->getMapsByGame($game, true);

        $this->assertCount(1, $result);
    }
}
