<template>
  <div>
    <h1>{{ t('reviews.title') }}</h1>
    <form class="card review-form" @submit.prevent="submit">
      <div class="form-group">
        <label>{{ t('reviews.rating') }}</label>
        <select v-model.number="form.rating">
          <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
        </select>
      </div>
      <div class="form-group">
        <label>{{ t('reviews.content') }}</label>
        <textarea v-model="form.content" />
      </div>
      <button type="submit" class="btn">{{ t('reviews.submit') }}</button>
      <p v-if="msg" class="success">{{ msg }}</p>
    </form>
    <div class="reviews-list">
      <div v-for="review in reviews" :key="review.id" class="card review-item">
        <div class="stars">{{ '★'.repeat(review.rating) }}</div>
        <p>{{ review.content }}</p>
        <div class="meta">
          <span>{{ review.username }}</span>
          <span v-if="!review.is_approved" class="pending">{{ t('reviews.pending') }}</span>
        </div>
      </div>
      <p v-if="!reviews.length && !loading" class="empty">{{ t('common.noData') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { api } from '@/api/client'

const { t } = useI18n()
const reviews = ref([])
const loading = ref(true)
const msg = ref('')
const form = ref({ rating: 5, content: '' })

onMounted(load)

async function load() {
  loading.value = true
  try {
    const result = await api('reviews.list')
    reviews.value = result.reviews || []
  } finally {
    loading.value = false
  }
}

async function submit() {
  msg.value = ''
  try {
    await api('reviews.create', form.value)
    msg.value = t('common.success')
    form.value = { rating: 5, content: '' }
    await load()
  } catch (e) {
    msg.value = e.message
  }
}
</script>

<style scoped>
h1 {
  margin-bottom: 1.5rem;
}

.review-form {
  margin-bottom: 1.5rem;
}

.reviews-list {
  display: grid;
  gap: 1rem;
}

.stars {
  color: #f59e0b;
  margin-bottom: 0.35rem;
}

.meta {
  display: flex;
  gap: 1rem;
  margin-top: 0.5rem;
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.pending {
  color: var(--accent);
}

.success {
  color: var(--accent);
  margin-top: 0.5rem;
}

.empty {
  color: var(--text-secondary);
  text-align: center;
  padding: 2rem;
}
</style>
