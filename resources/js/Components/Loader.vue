<template>
  <div class="loader">
    <div class="spinner"></div>
  </div>
</template>

  <script setup>
// Здесь можно добавить любые скрипты, если это необходимо
</script>

  <style scoped>
.loader {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100px; /* Задайте высоту, чтобы центрировать спиннер */
}

.spinner {
  border: 8px solid #f3f3f3;
  border-top: 8px solid #3498db;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>
