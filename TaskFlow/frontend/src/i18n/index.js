import { createI18n } from 'vue-i18n'
import ru from './locales/ru.json'
import en from './locales/en.json'

const savedLang = localStorage.getItem('language') || 'ru'

export default createI18n({
  legacy: false,
  locale: savedLang,
  fallbackLocale: 'ru',
  messages: { ru, en },
})
