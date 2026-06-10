<template>
  <router-view />
</template>

<script setup>
import { watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSettingsStore } from './stores/settings'

const settings = useSettingsStore()
const { locale } = useI18n()

watch(
  () => settings.language,
  (lang) => {
    locale.value = lang
  },
  { immediate: true }
)
</script>
