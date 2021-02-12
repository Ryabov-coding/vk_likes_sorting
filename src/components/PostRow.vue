<template>
    <tr class='table-row'>
        <td class='first-row'>
            {{post_index}}
        </td>
        <td class='second-row like-count'>
            {{formattedLikes}}
        </td>
        <td class='third-row'>
            <a id='url' :href="url" target="_blank">
                {{$t('posts.look')}}
            </a>
        </td>
        <td class='forth-row'>
            {{formattedHour}}:{{min}} {{amPm}}
        </td>
        <td class='fivth-row'>
            {{formattedWeekday}}
        </td>
        <td class='sixth-row'>
          {{day}} {{formattedMonth}}
        </td>
        <td class='seven-row year'>
            {{year}}
        </td>
    </tr>
</template>

<script>
    import Helpers from "@/helpers";

    export default {
        name: 'postRow',
        props: [
            'post_index',
            'likes',
            'url',
            'hour',
            'min',
            'am_pm',
            'weekday',
            'day',
            'month',
            'year',
        ],
        computed: {
          formattedMonth() {
            return this.$t('posts.dates.months.' + this.month)
          },
          formattedWeekday() {
            return this.$t('posts.dates.weekdays.' + this.weekday)
          },
          formattedHour() {
            return this.$t('posts.dates.hours.' + this.hour)
          },
          amPm(){
            return this.$i18n.locale === 'en'
              ? this.am_pm
              : ''
          },
          formattedLikes(){
            return Helpers.formatNumber(this.likes,0, ' ', ' ')
          }
        },
    }
</script>