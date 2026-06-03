<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import logoUrl from "@/assets/logo.svg";
import FeatureCard from "@/components/FeatureCard.vue";
import StatCard from "@/components/StatCard.vue";

const isLoggingIn = ref(false);
const appUrl = import.meta.env.VITE_APP_URL;

const stats = ref<Record<string, number> | null>(null);
const displayUsers = ref(0);
const displaySigns = ref(0);
const statsError = ref(false);

const cdnLatency = computed(() => {
  if (stats.value?.cdn_latency_ms == null) return null;
  return stats.value.cdn_latency_ms;
});

const uptimeStat = computed(() => {
  if (stats.value?.uptime_percentage != null) {
    return {
      value: String(stats.value.uptime_percentage) + "%",
    };
  }
  if (stats.value?.server_uptime_seconds != null) {
    const s = stats.value.server_uptime_seconds;
    const d = Math.floor(s / 86400);
    const h = Math.floor((s % 86400) / 3600);
    return { value: `${d}d ${h}h` };
  }
  return null;
});

const latencyStat = computed(() => {
  if (cdnLatency.value != null) {
    return { value: String(cdnLatency.value), suffix: "ms" as const };
  }
  return null;
});

function animateValue(
  target: number,
  setter: (v: number) => void,
  duration = 1500,
) {
  const start = performance.now();
  function frame(now: number) {
    const elapsed = now - start;
    const progress = Math.min(elapsed / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3);
    setter(Math.floor(eased * target));
    if (progress < 1) requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);
}

async function fetchStats() {
  try {
    const res = await fetch(`${import.meta.env.VITE_API_URL}api/stats`);
    const data = await res.json();
    stats.value = data;
    animateValue(data.total_users, (v) => (displayUsers.value = v));
    animateValue(data.total_signs, (v) => (displaySigns.value = v));
  } catch {
    statsError.value = true;
  }
}

onMounted(fetchStats);

async function loginWithDiscord() {
  if (isLoggingIn.value) return;
  isLoggingIn.value = true;

  try {
    const res = await fetch(
      `${import.meta.env.VITE_API_URL}api/auth/discord/redirect`,
    );
    const { url } = await res.json();
    window.location.assign(url);
  } catch {
    isLoggingIn.value = false;
  }
}
</script>

<template>
  <div class="relative flex h-screen flex-col bg-background">
    <div class="pointer-events-none fixed inset-0 -z-10" aria-hidden="true">
      <div
        class="absolute -left-64 -top-64 size-125 rounded-full bg-ambient-left opacity-60 blur-[150px]"
      />
      <div
        class="absolute -bottom-64 -right-64 size-125 rounded-full bg-ambient-right opacity-60 blur-[150px]"
      />
    </div>

    <header
      class="flex h-16 w-full flex-shrink-0 justify-center bg-background/60 px-6 backdrop-blur-md sm:px-8"
    >
      <nav class="mx-auto flex w-full max-w-7xl items-center justify-between">
        <div class="flex items-center gap-x-2">
          <img :src="logoUrl" alt="SignVault logo" class="mt-1.5 size-9" />
          <p class="text-[32px] font-medium text-zinc-100 no-underline">
            Sign<span class="font-bold text-emerald-400">Vault</span>
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button
            type="button"
            :disabled="isLoggingIn"
            class="cursor-pointer flex items-center gap-x-1 rounded-md bg-emerald-400 px-4 py-1.5 text-sm font-semibold text-background no-underline transition hover:bg-emerald-200 disabled:opacity-75"
            @click="loginWithDiscord"
          >
            <svg
              width="16px"
              height="16px"
              viewBox="0 0 24 24"
              class="-ml-1"
              xmlns="http://www.w3.org/2000/svg"
              xml:space="preserve"
            >
              <path
                d="M18.942 5.556a16.299 16.299 0 0 0-4.126-1.297c-.178.321-.385.754-.529 1.097a15.175 15.175 0 0 0-4.573 0 11.583 11.583 0 0 0-.535-1.097 16.274 16.274 0 0 0-4.129 1.3c-2.611 3.946-3.319 7.794-2.965 11.587a16.494 16.494 0 0 0 5.061 2.593 12.65 12.65 0 0 0 1.084-1.785 10.689 10.689 0 0 1-1.707-.831c.143-.106.283-.217.418-.331 3.291 1.539 6.866 1.539 10.118 0 .137.114.277.225.418.331-.541.326-1.114.606-1.71.832a12.52 12.52 0 0 0 1.084 1.785 16.46 16.46 0 0 0 5.064-2.595c.415-4.396-.709-8.209-2.973-11.589zM8.678 14.813c-.988 0-1.798-.922-1.798-2.045s.793-2.047 1.798-2.047 1.815.922 1.798 2.047c.001 1.123-.793 2.045-1.798 2.045zm6.644 0c-.988 0-1.798-.922-1.798-2.045s.793-2.047 1.798-2.047 1.815.922 1.798 2.047c0 1.123-.793 2.045-1.798 2.045z"
              />
            </svg>
            {{ isLoggingIn ? "Redirecting..." : "Login" }}
          </button>
        </div>
      </nav>
    </header>

    <main class="relative z-10 flex-1 overflow-y-auto">
      <section
        class="mx-auto flex max-w-7xl flex-col items-center justify-center px-6 h-full sm:px-8"
      >
        <div class="mb-12 text-center">
          <h1
            class="mb-6 text-4xl font-bold tracking-tight text-heading sm:text-5xl lg:text-6xl"
          >
            Your Trackmania Signs,<br />
            <span class="text-emerald-400">Organized</span>
          </h1>
          <p class="mx-auto max-w-2xl text-lg text-text sm:text-xl">
            Host, browse, and share Trackmania sign collections with the
            community. Upload your signs and access them from anywhere.
          </p>
        </div>

        <div class="mb-12 flex items-center justify-center gap-x-2">
          <button
            type="button"
            :disabled="isLoggingIn"
            class="inline-flex gap-x-2 cursor-pointer items-center rounded-md bg-emerald-400 px-6 py-3 text-base font-semibold text-background no-underline shadow-lg shadow-emerald-400/20 transition hover:bg-emerald-200 disabled:opacity-75"
            @click="loginWithDiscord"
          >
            <svg
              width="24px"
              height="24px"
              viewBox="0 0 24 24"
              class="-ml-1 fill-background"
              xmlns="http://www.w3.org/2000/svg"
              xml:space="preserve"
            >
              <path
                d="M18.942 5.556a16.299 16.299 0 0 0-4.126-1.297c-.178.321-.385.754-.529 1.097a15.175 15.175 0 0 0-4.573 0 11.583 11.583 0 0 0-.535-1.097 16.274 16.274 0 0 0-4.129 1.3c-2.611 3.946-3.319 7.794-2.965 11.587a16.494 16.494 0 0 0 5.061 2.593 12.65 12.65 0 0 0 1.084-1.785 10.689 10.689 0 0 1-1.707-.831c.143-.106.283-.217.418-.331 3.291 1.539 6.866 1.539 10.118 0 .137.114.277.225.418.331-.541.326-1.114.606-1.71.832a12.52 12.52 0 0 0 1.084 1.785 16.46 16.46 0 0 0 5.064-2.595c.415-4.396-.709-8.209-2.973-11.589zM8.678 14.813c-.988 0-1.798-.922-1.798-2.045s.793-2.047 1.798-2.047 1.815.922 1.798 2.047c.001 1.123-.793 2.045-1.798 2.045zm6.644 0c-.988 0-1.798-.922-1.798-2.045s.793-2.047 1.798-2.047 1.815.922 1.798 2.047c0 1.123-.793 2.045-1.798 2.045z"
              />
            </svg>
            <span>{{ isLoggingIn ? "Redirecting..." : "Login with Discord" }}</span>
          </button>
          <a
            :href="appUrl"
            target="_blank"
            class="rounded-md border border-border px-6 py-3 text-base font-semibold text-zinc-100 no-underline transition hover:bg-white/5"
          >
            Browse signs
          </a>
        </div>

        <div class="mb-12 grid w-full max-w-4xl gap-6 sm:grid-cols-4">
          <StatCard label="users" :value="displayUsers.toLocaleString()" />

          <StatCard label="signs" :value="displaySigns.toLocaleString()" />

          <StatCard
            label="uptime"
            :value="
              uptimeStat?.value ?? (statsError ? '\u2014' : 'checking\u2026')
            "
            :muted="uptimeStat == null"
          />

          <StatCard
            label="latency"
            :value="
              latencyStat?.value ?? (statsError ? '\u2014' : 'checking\u2026')
            "
            :suffix="latencyStat?.suffix"
            :muted="latencyStat == null"
          />
        </div>

        <div class="grid gap-6 sm:grid-cols-3 lg:max-w-5xl">
          <FeatureCard
            title="Browse with Previews"
            description="Explore public sign collections from the community. Every folder shows image previews so you can see exactly what you're getting before you download."
          >
            <template #icon>
              <svg
                class="size-5 text-emerald-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
              </svg>
            </template>
          </FeatureCard>

          <FeatureCard
            title="Flexible Visibility"
            description="Keep folders private for drafts, password-protect them for early access, or publish them publicly to the whole community. You're in control."
          >
            <template #icon>
              <svg
                class="size-5 text-emerald-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"
                />
              </svg>
            </template>
          </FeatureCard>

          <FeatureCard
            title="Discord Login"
            description="Jump right in with your Discord account. No sign-up forms, no extra passwords — just one click and you're in."
          >
            <template #icon>
              <svg
                class="size-5 text-emerald-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"
                />
              </svg>
            </template>
          </FeatureCard>
        </div>
      </section>
    </main>

    <footer
      class="border-t border-border px-6 py-4 text-center text-xs text-zinc-400 sm:px-8"
    >
      <div class="flex items-center justify-center gap-4">
        <span>SignVault - Trackmania sign library</span>
        <a
          :href="appUrl + 'terms'"
          target="_blank"
          class="text-zinc-500 no-underline transition-colors hover:text-zinc-100"
        >
          Terms
        </a>
        <a
          :href="appUrl + 'privacy'"
          target="_blank"
          class="text-zinc-500 no-underline transition-colors hover:text-zinc-100"
        >
          Privacy
        </a>
        <a
          href="https://github.com/Dubbie/sign-vault-frontend"
          target="_blank"
          rel="noopener noreferrer"
          class="text-zinc-500 no-underline transition-colors hover:text-zinc-100"
        >
          Source
        </a>
        <a
          href="https://discord.gg/vkaXfkr4qa"
          target="_blank"
          rel="noopener noreferrer"
          class="text-zinc-500 no-underline transition-colors hover:text-zinc-100"
        >
          Discord
        </a>
      </div>
    </footer>
  </div>
</template>
