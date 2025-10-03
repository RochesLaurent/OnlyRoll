<?php

namespace App\DataFixtures;

use App\Entity\Game;
use App\Entity\GamePlayer;
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
        for ($i = 1; $i <= 3; ++$i) {
            $game = new Game();
            $game->setName("Campagne de Test #{$i}")
                 ->setDescription("Une campagne D&D 5e épique pour tester l'application")
                 ->setGameMaster($users[0])
                 ->setMaxPlayers(6)
                 ->setIsPublic(3 !== $i)
                 ->setStatus(1 === $i ? GameStatus::IN_PROGRESS : GameStatus::PREPARATION);

            $manager->persist($game);

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

        $manager->flush();
    }
}
