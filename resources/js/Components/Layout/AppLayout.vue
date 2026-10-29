<template>
  <div class="app-shell">
    <!-- Sidebar Navigation Nota Menta Claro -->
    <Sidebar />

    <!-- Main Content Workspace -->
    <div class="app-content">
      <!-- Navbar Header -->
      <Header />

      <!-- Scrollable Main Content -->
      <main class="app-main">
        <slot />
      </main>

      <!-- Footer Visits Stats -->
      <Footer />
    </div>
  </div>
</template>

<script setup>
import Sidebar from './Sidebar.vue';
import Header from './Header.vue';
import Footer from './Footer.vue';
</script>

<style scoped>
.app-shell {
  display: flex;
  height: 100vh;
  overflow: hidden;
  background-color: var(--bg-page);
  color: var(--text-body);
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

.app-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  overflow: hidden;
}

.app-main {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
  background-color: var(--bg-page);
}
</style>
