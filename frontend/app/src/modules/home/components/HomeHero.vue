<script setup lang="ts">
// Bandeau d'accueil (maquette Accueil, rimef-handoff/bandeau.png) : salutation sur fond
// ivory à gauche, photo à droite séparée par une courbe, deux feuilles à cheval sur la courbe.
import { LEAF_PATH, leafTransform } from '@/lib/leaf';

defineProps<{ firstName: string }>();
</script>

<template>
  <section class="home-hero">
    <div class="home-hero__visual">
      <!-- Photo d'ambiance décorative : alt vide, ignorée par les lecteurs d'écran -->
      <img
        src="/images/hero.webp"
        srcset="/images/hero-800.webp 800w, /images/hero.webp 1536w"
        sizes="(max-width: 719px) 100vw, 56vw"
        width="1536"
        height="1024"
        alt=""
        class="home-hero__photo"
        fetchpriority="high"
      />
      <svg
        class="home-hero__curve"
        viewBox="0 0 100 100"
        preserveAspectRatio="none"
        aria-hidden="true"
      >
        <path d="M0 0 H9 C 22 26, 30 58, 52 100 H0 Z" fill="var(--ivory)" />
      </svg>
      <svg
        class="home-hero__curve-bottom"
        viewBox="0 0 100 20"
        preserveAspectRatio="none"
        aria-hidden="true"
      >
        <path d="M0 20 V8 C 30 22, 65 -2, 100 10 V20 Z" fill="var(--ivory)" />
      </svg>
      <svg class="home-hero__leaves" viewBox="0 0 190 300" aria-hidden="true">
        <path
          :d="LEAF_PATH"
          :transform="leafTransform(118, 196, -122, 190, 64)"
          fill="#C86F55"
          opacity="0.92"
        />
        <path
          :d="LEAF_PATH"
          :transform="leafTransform(22, 236, 18, 176, 60)"
          fill="#8FA89C"
          opacity="0.9"
        />
      </svg>
    </div>

    <div class="container home-hero__container">
      <div class="home-hero__text">
        <h1 class="home-hero__title">Bonjour {{ firstName }},</h1>
        <p class="home-hero__lead">Voici ce qui se passe dans votre réseau.</p>
      </div>
    </div>
  </section>
</template>

<style lang="scss" scoped>
.home-hero {
  position: relative;
  overflow: hidden;
  background: var(--ivory);
  border-bottom: 1px solid var(--border);

  &__container {
    position: relative;
    display: flex;
    align-items: center;
    min-height: 200px;
  }

  &__text {
    position: relative;
    z-index: 3;
    width: 44%;
    padding-block: 28px;
  }

  &__title {
    font-size: var(--fs-display);
    line-height: 1.02;
    letter-spacing: -0.01em;
    color: var(--petrol-dark);
  }

  &__lead {
    margin-top: 14px;
    font-size: 1.25rem;
    font-weight: 500;
    letter-spacing: -0.005em;
    color: var(--petrol);
  }

  // Photo à droite, de la marge du conteneur au bord de l'écran
  &__visual {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    width: max(56%, calc(100% - (100% - var(--container)) / 2 - var(--container) * 0.47));
  }

  &__photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: 60% 38%;
  }

  &__curve {
    position: absolute;
    z-index: 1;
    top: 0;
    bottom: 0;
    left: -1px;
    width: 34%;
    height: 100%;
  }

  &__curve-bottom {
    display: none;
  }

  // Feuilles réduites à la hauteur du bandeau
  &__leaves {
    position: absolute;
    z-index: 2;
    top: 50%;
    left: -16px;
    width: 130px;
    height: 200px;
    transform: translateY(-50%);
  }
}

@media (max-width: 1079px) {
  .home-hero {
    &__container {
      min-height: 190px;
    }

    &__text {
      width: 50%;
    }

    &__visual {
      width: 52%;
    }

    &__leaves {
      left: -48px;
      width: 125px;
      height: 190px;
    }
  }
}

// Téléphone : photo en haut sur toute la largeur, texte en dessous
@media (max-width: 719px) {
  .home-hero {
    &__container {
      min-height: 0;
    }

    &__visual {
      position: relative;
      width: 100%;
      aspect-ratio: 16 / 7;
    }

    &__curve {
      display: none;
    }

    &__curve-bottom {
      position: absolute;
      z-index: 1;
      right: 0;
      bottom: -1px;
      left: 0;
      display: block;
      width: 100%;
      height: 32px;
    }

    &__leaves {
      top: auto;
      right: 8px;
      bottom: -34px;
      left: auto;
      width: 72px;
      height: 112px;
      transform: rotate(18deg);
    }

    &__text {
      width: auto;
      padding: var(--s-2) 0 30px;
    }

    &__title {
      font-size: 2.35rem;
    }

    &__lead {
      margin-top: 10px;
      font-size: 1.08rem;
    }
  }
}
</style>
