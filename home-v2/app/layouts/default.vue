<template>
  <div class="min-h-screen bg-zinc-950 text-zinc-100">
    <nav class="border-b border-zinc-800 px-4 py-3 flex items-center gap-4">
      <NuxtLink to="/" class="font-semibold text-lg">driip-</NuxtLink>
      <div class="flex-1" />
      <NuxtLink to="/account" class="text-sm text-zinc-300 hover:text-white">
        Account
      </NuxtLink>
    </nav>
    <main>
      <slot />
    </main>
  </div>
</template>
