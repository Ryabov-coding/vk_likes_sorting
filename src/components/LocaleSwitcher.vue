<template>
  <div class="locale-switcher">
      <span v-for="locale in locales"
            :key="locale"
            @click="switchLocale(locale)"
            class="locale-switcher__locale">{{ locale }}</span>
  </div>
</template>

<script>
import EventBus from '../event-bus'

export default {
  name: "LocaleSwitcher",
  data() {
    return {
    }
  },
  methods: {
    switchLocale(locale) {
      if (this.$i18n.locale !== locale) {
        this.$i18n.locale = locale
        document.title = this.$t('doc-title')
        EventBus.$emit('locale_change', locale);
      }
    }
  },
  computed: {
    locales() {
		return process.env.VUE_APP_I18N_SUPPORTED_LOCALES.split(',')
    }
  }
}
</script>

<style scoped>
  .locale-switcher {
    position: absolute;
    top: -30px;
    left: 1px;
    font-size: 19px;
  }

  .locale-switcher__locale {
    cursor: pointer;
    text-decoration: underline;
    margin-right: 5px;
    font-weight: 700;
    color: white;
  }

  .locale-switcher__locale:hover {
    color: #d8d8d8;
  }
</style>
