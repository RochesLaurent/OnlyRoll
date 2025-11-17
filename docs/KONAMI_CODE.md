# Easter Egg : Konami Code 🎮

## Description

Un easter egg amusant a été intégré dans OnlyRoll : le célèbre **Konami Code** !

Lorsque la séquence correcte est entrée, une animation spectaculaire se déclenche avec :
- 🎆 Explosion de confettis multicolores
- 🎮 Message "KONAMI CODE VICTORY!" en typographie rétro
- 📊 Animation de score façon arcade
- 🔊 Son rétro 8-bit (si disponible)

## Comment l'activer ?

Tapez la séquence suivante avec votre clavier :

```
↑ ↑ ↓ ↓ ← → ← → B A
```

En détail :
1. Flèche HAUT (×2)
2. Flèche BAS (×2)
3. Flèche GAUCHE
4. Flèche DROITE
5. Flèche GAUCHE
6. Flèche DROITE
7. Touche B
8. Touche A

## Architecture technique

### Fichiers créés

```
frontend/
├── src/
│   ├── composables/
│   │   └── useKonamiCode.ts          # Détection de la séquence
│   ├── components/
│   │   └── common/
│   │       └── KonamiVictory.vue     # Composant d'affichage
│   └── App.vue                        # Intégration globale
└── public/
    └── sounds/
        └── konami.mp3                 # Son rétro (optionnel)
```

### Technologies utilisées

- **canvas-confetti** : Animation de confettis
- **Vue 3 Composition API** : Gestion réactive
- **Teleport** : Affichage en overlay full-screen
- **Google Fonts - Press Start 2P** : Typographie rétro Konami

## Utilisation du composable

Le composable `useKonamiCode` peut être réutilisé dans n'importe quel composant :

```typescript
import { useKonamiCode } from '@/composables/useKonamiCode'

// Utilisation simple avec callback
const { isActivated, reset } = useKonamiCode(() => {
  console.log('Konami Code activé!')
})

// Ou en observant la valeur réactive
watch(isActivated, (active) => {
  if (active) {
    // Logique personnalisée
  }
})
```

## Personnalisation

### Modifier la séquence

Éditez le tableau `konamiSequence` dans [useKonamiCode.ts](../frontend/src/composables/useKonamiCode.ts) :

```typescript
const konamiSequence = [
  'ArrowUp',
  'ArrowUp',
  // ... votre séquence personnalisée
]
```

### Changer l'animation

Le composant [KonamiVictory.vue](../frontend/src/components/common/KonamiVictory.vue) peut être personnalisé :

- **Confettis** : Modifiez les paramètres dans `triggerConfetti()`
- **Score** : Changez `targetScore` et la durée d'animation
- **Style** : Adaptez les CSS avec vos couleurs et effets

### Ajouter un son personnalisé

1. Placez votre fichier audio dans `frontend/public/sounds/`
2. Renommez-le `konami.mp3` (ou modifiez le chemin dans le composant)
3. Formats recommandés : MP3, OGG, WAV
4. Durée idéale : 1-3 secondes

**Sources de sons rétro gratuits :**
- [Freesound.org](https://freesound.org/search/?q=8bit+victory)
- [Bfxr.net](https://www.bfxr.net/) (générateur)
- [Zapsplat.com](https://www.zapsplat.com/)

## Tests

Pour tester rapidement en développement, vous pouvez :

1. Ouvrir la console navigateur
2. Simuler la séquence :

```javascript
// Simuler la séquence complète
const keys = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown',
              'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight',
              'KeyB', 'KeyA']

keys.forEach((key, index) => {
  setTimeout(() => {
    window.dispatchEvent(new KeyboardEvent('keydown', { code: key }))
  }, index * 100)
})
```

## Performance

- **Poids** : ~15 KB (composable + composant)
- **Dépendances** : canvas-confetti (~11 KB)
- **Impact** : Négligeable (détection passive, activation à la demande)
- **Compatibilité** : Tous navigateurs modernes

## Fun Facts

Le Konami Code est l'un des easter eggs les plus célèbres du jeu vidéo, créé par Kazuhisa Hashimoto en 1986 pour le jeu *Gradius* sur NES. Il a été réutilisé dans des centaines de jeux et sites web depuis !

**Quelques implémentations célèbres :**
- Contra (NES) : 30 vies
- Gradius (NES) : Power-ups complets
- Site Konami : Animation spéciale
- BuzzFeed : Confettis de licornes

---

**Amusez-vous bien ! 🎮✨**
