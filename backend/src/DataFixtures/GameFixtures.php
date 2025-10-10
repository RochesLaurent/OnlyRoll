<?php

namespace App\DataFixtures;

use App\Entity\Game;
use App\Entity\GameMap;
use App\Entity\GameMessage;
use App\Entity\GamePlayer;
use App\Entity\GameToken;
use App\Entity\User;
use App\Enum\GameStatus;
use App\Enum\PlayerRole;
use App\Enum\PlayerStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class GameFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Créer des utilisateurs
        $users = [];
        for ($i = 1; $i <= 5; ++$i) {
            $user = new User();
            $user->setPseudo("player{$i}")
                ->setEmail("player{$i}@onlyroll.com")
                ->setPassword(password_hash('password', PASSWORD_BCRYPT))
                ->setRoles(['ROLE_USER'])
                ->setIsVerified(true);
            $manager->persist($user);
            $users[] = $user;
        }

        // Créer des parties
        $games = [];
        for ($i = 1; $i <= 3; ++$i) {
            $game = new Game();
            $game->setName("Campagne de Test #{$i}")
                ->setDescription("Une campagne D&D 5e épique pour tester l'application")
                ->setGameMaster($users[0])
                ->setMaxPlayers(6)
                ->setIsPublic(3 !== $i)
                ->setStatus(1 === $i ? GameStatus::IN_PROGRESS : GameStatus::PREPARATION);
            $manager->persist($game);
            $games[] = $game;

            // Ajouter le GM
            $gmPlayer = new GamePlayer();
            $gmPlayer->setGame($game)
                ->setUser($users[0])
                ->setRole(PlayerRole::GAME_MASTER)
                ->setStatus(PlayerStatus::ACTIVE);
            $manager->persist($gmPlayer);

            // Ajouter quelques joueurs
            for ($j = 1; $j <= min(3, count($users) - 1); ++$j) {
                $player = new GamePlayer();
                $player->setGame($game)
                    ->setUser($users[$j])
                    ->setRole(PlayerRole::PLAYER)
                    ->setStatus(PlayerStatus::ACTIVE);
                $manager->persist($player);
            }
        }

        // Créer des cartes pour la première partie (en cours)
        $this->createMapsForGame($manager, $games[0]);

        // Créer des messages de chat pour la première partie
        $this->createMessagesForGame($manager, $games[0], $users);

        $manager->flush();
    }

    /**
     * Créer des cartes et des tokens pour une partie.
     */
    private function createMapsForGame(ObjectManager $manager, Game $game): void
    {
        // Carte 1 : Taverne (active)
        $tavern = new GameMap();
        $tavern->setGame($game)
            ->setName('La Taverne du Dragon Vert')
            ->setDescription('Une taverne chaleureuse où les aventuriers se rassemblent')
            ->setWidth(30)
            ->setHeight(25)
            ->setGridSize(50)
            ->setGridType('square')
            ->setIsActive(true)
            ->setSettings([
                'backgroundColor' => '#2a1810',
                'gridColor' => '#ffffff',
                'gridOpacity' => 0.2,
                'showGrid' => true,
            ]);
        $manager->persist($tavern);

        // Tokens pour la taverne
        $this->createTokensForMap($manager, $tavern);

        // Carte 2 : Donjon (inactive)
        $dungeon = new GameMap();
        $dungeon->setGame($game)
            ->setName('Les Cryptes Oubliées')
            ->setDescription('Un donjon sombre et dangereux sous la ville')
            ->setWidth(40)
            ->setHeight(35)
            ->setGridSize(50)
            ->setGridType('square')
            ->setIsActive(false)
            ->setSettings([
                'backgroundColor' => '#0a0a0a',
                'gridColor' => '#444444',
                'gridOpacity' => 0.3,
                'showGrid' => true,
            ]);
        $manager->persist($dungeon);

        // Tokens pour le donjon
        $tokens = [
            ['name' => 'Squelette', 'type' => 'monster', 'x' => 15, 'y' => 10],
            ['name' => 'Zombie', 'type' => 'monster', 'x' => 18, 'y' => 12],
            ['name' => 'Trésor', 'type' => 'object', 'x' => 30, 'y' => 25],
        ];

        foreach ($tokens as $tokenData) {
            $token = new GameToken();
            $token->setMap($dungeon)
                ->setName($tokenData['name'])
                ->setType($tokenData['type'])
                ->setX($tokenData['x'])
                ->setY($tokenData['y'])
                ->setSize(1.0)
                ->setRotation(0)
                ->setIsVisible(true)
                ->setIsLocked(false)
                ->setLayer('tokens');
            $manager->persist($token);
        }

        // Carte 3 : Forêt (inactive)
        $forest = new GameMap();
        $forest->setGame($game)
            ->setName('La Forêt Enchantée')
            ->setDescription('Une forêt mystérieuse pleine de dangers')
            ->setWidth(50)
            ->setHeight(50)
            ->setGridSize(50)
            ->setGridType('hex')
            ->setIsActive(false)
            ->setSettings([
                'backgroundColor' => '#1a3a1a',
                'gridColor' => '#88cc88',
                'gridOpacity' => 0.25,
                'showGrid' => true,
            ]);
        $manager->persist($forest);
    }

    /**
     * Créer des tokens pour une carte.
     */
    private function createTokensForMap(ObjectManager $manager, GameMap $map): void
    {
        // Personnages des joueurs
        $characters = [
            [
                'name' => 'Thorin Barbe-de-Fer',
                'type' => 'character',
                'x' => 10,
                'y' => 12,
                'size' => 1.0,
                'visible' => true,
                'settings' => [
                    'healthPoints' => 45,
                    'maxHealthPoints' => 50,
                    'armorClass' => 16,
                    'initiative' => 2,
                ],
            ],
            [
                'name' => 'Elara la Sage',
                'type' => 'character',
                'x' => 12,
                'y' => 12,
                'size' => 1.0,
                'visible' => true,
                'settings' => [
                    'healthPoints' => 28,
                    'maxHealthPoints' => 30,
                    'armorClass' => 13,
                    'initiative' => 5,
                ],
            ],
            [
                'name' => 'Grimm le Voleur',
                'type' => 'character',
                'x' => 11,
                'y' => 14,
                'size' => 1.0,
                'visible' => true,
                'settings' => [
                    'healthPoints' => 32,
                    'maxHealthPoints' => 35,
                    'armorClass' => 15,
                    'initiative' => 8,
                ],
            ],
        ];

        foreach ($characters as $charData) {
            $token = new GameToken();
            $token->setMap($map)
                ->setName($charData['name'])
                ->setType($charData['type'])
                ->setX($charData['x'])
                ->setY($charData['y'])
                ->setSize($charData['size'])
                ->setRotation(0)
                ->setIsVisible($charData['visible'])
                ->setIsLocked(false)
                ->setLayer('tokens')
                ->setSettings($charData['settings']);
            $manager->persist($token);
        }

        // NPCs
        $npcs = [
            ['name' => 'Aubergiste', 'type' => 'npc', 'x' => 15, 'y' => 8],
            ['name' => 'Marchand', 'type' => 'npc', 'x' => 18, 'y' => 10],
        ];

        foreach ($npcs as $npcData) {
            $token = new GameToken();
            $token->setMap($map)
                ->setName($npcData['name'])
                ->setType($npcData['type'])
                ->setX($npcData['x'])
                ->setY($npcData['y'])
                ->setSize(1.0)
                ->setRotation(0)
                ->setIsVisible(true)
                ->setIsLocked(false)
                ->setLayer('tokens');
            $manager->persist($token);
        }

        // Monstres (cachés par défaut)
        $monsters = [
            ['name' => 'Gobelin Archer', 'type' => 'monster', 'x' => 25, 'y' => 15, 'visible' => false],
            ['name' => 'Gobelin Guerrier', 'type' => 'monster', 'x' => 26, 'y' => 16, 'visible' => false],
            ['name' => 'Chef Gobelin', 'type' => 'monster', 'x' => 27, 'y' => 15, 'visible' => false],
        ];

        foreach ($monsters as $monsterData) {
            $token = new GameToken();
            $token->setMap($map)
                ->setName($monsterData['name'])
                ->setType($monsterData['type'])
                ->setX($monsterData['x'])
                ->setY($monsterData['y'])
                ->setSize(1.0)
                ->setRotation(0)
                ->setIsVisible($monsterData['visible'])
                ->setIsLocked(false)
                ->setLayer('tokens')
                ->setSettings([
                    'healthPoints' => 15,
                    'maxHealthPoints' => 15,
                    'armorClass' => 13,
                ]);
            $manager->persist($token);
        }

        // Objets
        $objects = [
            ['name' => 'Table', 'type' => 'object', 'x' => 5, 'y' => 5, 'size' => 2.0],
            ['name' => 'Chaise', 'type' => 'object', 'x' => 5, 'y' => 7, 'size' => 1.0],
            ['name' => 'Bar', 'type' => 'object', 'x' => 15, 'y' => 5, 'size' => 3.0],
        ];

        foreach ($objects as $objData) {
            $token = new GameToken();
            $token->setMap($map)
                ->setName($objData['name'])
                ->setType($objData['type'])
                ->setX($objData['x'])
                ->setY($objData['y'])
                ->setSize($objData['size'])
                ->setRotation(0)
                ->setIsVisible(true)
                ->setIsLocked(true) // Les objets sont verrouillés par défaut
                ->setLayer('objects');
            $manager->persist($token);
        }
    }

    /**
     * Créer des messages de chat pour une partie.
     *
     * @param User[] $users
     */
    private function createMessagesForGame(ObjectManager $manager, Game $game, array $users): void
    {
        $messages = [
            [
                'user' => $users[0],
                'type' => GameMessage::TYPE_SYSTEM,
                'content' => 'La partie commence ! Bienvenue à tous.',
                'isInCharacter' => false,
            ],
            [
                'user' => $users[1],
                'type' => GameMessage::TYPE_CHAT,
                'content' => 'Prêt pour l\'aventure !',
                'isInCharacter' => false,
            ],
            [
                'user' => $users[1],
                'type' => GameMessage::TYPE_CHAT,
                'content' => 'Je m\'avance vers le bar pour commander une bière.',
                'isInCharacter' => true,
            ],
            [
                'user' => $users[2],
                'type' => GameMessage::TYPE_EMOTE,
                'content' => 'observe discrètement les autres clients de la taverne',
                'isInCharacter' => true,
            ],
            [
                'user' => $users[1],
                'type' => GameMessage::TYPE_DICE_ROLL,
                'content' => 'Jet de Perception',
                'isInCharacter' => true,
                'diceResult' => [
                    'config' => ['dice' => '1d20+3'],
                    'results' => [15],
                    'total' => 18,
                    'timestamp' => (new \DateTimeImmutable())->format('c'),
                ],
            ],
            [
                'user' => $users[0],
                'type' => GameMessage::TYPE_CHAT,
                'content' => 'Avec un 18, tu remarques un individu encapuchonné dans le coin.',
                'isInCharacter' => true,
            ],
            [
                'user' => $users[3],
                'type' => GameMessage::TYPE_CHAT,
                'content' => 'Est-ce que je peux essayer de m\'approcher discrètement ?',
                'isInCharacter' => false,
            ],
            [
                'user' => $users[0],
                'type' => GameMessage::TYPE_CHAT,
                'content' => 'Oui, fais un jet de Discrétion.',
                'isInCharacter' => false,
            ],
        ];

        foreach ($messages as $msgData) {
            $message = new GameMessage();
            $message->setGame($game)
                ->setUser($msgData['user'])
                ->setType($msgData['type'])
                ->setContent($msgData['content'])
                ->setIsInCharacter($msgData['isInCharacter']);

            if (isset($msgData['diceResult'])) {
                $message->setDiceResult($msgData['diceResult']);
            }

            $manager->persist($message);

            // Petit délai entre les messages pour avoir des timestamps différents
            usleep(100000); // 0.1 seconde
        }
    }
}
