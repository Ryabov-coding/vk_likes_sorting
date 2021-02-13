<template>
    <div class="wrap-list">
        <div class="info">
            <img :src="avatar_link" alt="Аватар группы">
            <div class="text">
                <h3>
                  {{name}}
                </h3>
                <h4 class="total-posts">
                  {{$t('posts.total-posts')}}: {{postsCounter}}
                </h4>
                <h4 class="total-likes">
                  {{$t('posts.total-likes')}}: {{likesCounter}}
                </h4>
                <h4 class="middle-likes">
                  {{$t('posts.middle-likes')}}: {{middleLikesPerPost}}
                </h4>
            </div>
        </div>

        <div class="extra-sort">
            <div class="btns">
                <div v-on:click="toggleFiltersMenu()" class="additional-btn btn-filter">
                  {{$t('posts.filters')}}
                </div>
                <div v-on:click="upendPosts()" class="additional-btn btn-upend">
                  {{$t('posts.upend')}}
                </div>
            </div>

            <div v-if="showFiltersMenu"
                 class="types">
                <div class="test">
                    <table>
                        <tr>
                            <th>
                                <input v-model="filterTime" id="check-hour" type="checkbox"
                                       name="filter-time" class="filter-checkbox">
                                <h4 id="check-hour-header">{{$t('posts.filter-time')}}</h4>
                            </th>
                            <th>
                                <input v-model="filterWeekday" id="check-weekday" type="checkbox"
                                       name="filter-weekday" class="filter-checkbox">
                                <h4 id="check-weekday-header">{{$t('posts.filter-weekday')}}</h4>
                            </th>
                            <th>
                                <input v-model="filterDate" id="check-date" type="checkbox"
                                       name="filter-date" class="filter-checkbox">
                                <h4 id="check-date-header">{{$t('posts.filter-date')}}</h4>
                            </th>
                        </tr>
                        <tr>
                            <td class="sort-first">
                                <div class="filter-hour-wrapper">
                                    <div class="filter-hour">
                                        <select v-model="filtersData.time" :disabled="!filterTime"
                                                id="hours" multiple class="form-control">
                                            <option value="24">00</option>
                                            <option value="1">01</option>
                                            <option value="2">02</option>
                                            <option value="3">03</option>
                                            <option value="4">04</option>
                                            <option value="5">05</option>
                                            <option value="6">06</option>
                                            <option value="7">07</option>
                                            <option value="8">08</option>
                                            <option value="9">09</option>
                                            <option value="10">10</option>
                                            <option value="11">11</option>
                                            <option value="12">12</option>
                                            <option value="13">13</option>
                                            <option value="14">14</option>
                                            <option value="15">15</option>
                                            <option value="16">16</option>
                                            <option value="17">17</option>
                                            <option value="18">18</option>
                                            <option value="19">19</option>
                                            <option value="20">20</option>
                                            <option value="21">21</option>
                                            <option value="22">22</option>
                                            <option value="23">23</option>
                                        </select>
                                    </div>
                                </div>
                            </td>
                            <td class="sort-second">
                                <div class="filter-weekday-wrapper">
                                    <div class="filter-date">
                                        <select v-model="filtersData.weekday" :disabled="!filterWeekday"
                                                id="week-day" class="form-control" multiple>
                                            <option value="1">{{ $t('posts.filters-weekdays.monday') }}</option>
                                            <option value="2">{{ $t('posts.filters-weekdays.tuesday') }}</option>
                                            <option value="3">{{ $t('posts.filters-weekdays.wednesday') }}</option>
                                            <option value="4">{{ $t('posts.filters-weekdays.thursday') }}</option>
                                            <option value="5">{{ $t('posts.filters-weekdays.friday') }}</option>
                                            <option value="6">{{ $t('posts.filters-weekdays.saturday') }}</option>
                                            <option value="7">{{ $t('posts.filters-weekdays.sunday') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </td>
                            <td class="sort-third">
                                <div class="filter-date-wrapper">
                                    <div class="filter-date">
                                        <DatePicker v-model="filtersData.date.from" :language="datePickerRuLang"
                                                    format="yyyy-MM-dd" :disabled="!filterDate" :typeable="true"
                                                    :placeholder="$t('posts.time-filter.from')"
                                                    input-class="datetimepicker-from"></DatePicker>
                                        <DatePicker v-model="filtersData.date.to" :language="datePickerRuLang"
                                                    format="yyyy-MM-dd" :disabled="!filterDate" :typeable="true"
                                                    :placeholder="$t('posts.time-filter.to')"
                                                    input-class="datetimepicker-to"></DatePicker>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <button v-on:click="filterPosts()" id="sort" class="btn btn-default">
                      {{$t('posts.filter-btn')}}
                    </button>
                </div>
            </div>
        </div>

        <div class="list">
            <table>
                <PostRow v-for="(post, post_index) in posts"
                         :key="post_index"
                         :index="post_index"
                         :post_index="post_index + 1"
                         :likes="post.likes"
                         :url="post.url"
                         :hour="post.datetime.hour"
                         :min="post.datetime.min"
                         :am_pm="post.datetime.am_pm"
                         :weekday="post.datetime.week_day"
                         :day="post.datetime.day"
                         :month="post.datetime.month"
                         :year="post.datetime.year">
                </PostRow>
            </table>
        </div>
        <h3 v-if="showMorePostsBtn"
            v-on:click="morePosts()"
            class="more">
          {{$t('posts.more')}} {{posts_per_page}}
        </h3>
    </div>
</template>

<script>
    import PostRow from '../components/PostRow.vue'
    import DatePicker from 'vuejs-datepicker';
    import {ru} from 'vuejs-datepicker/dist/locale'
    import Helpers from '../helpers'
    import EventBus from '../event-bus'

    export default {
        name: 'PublicFrame',
        props: [
            'name',
            'avatar_link',
            'posts_counter',
            'likes_counter',
            'posts',
            'posts_per_page'
        ],
        components: {
            PostRow,
            DatePicker,
        },
        mounted() {
            // Меняем абривиатуры в датапикере
            this.setCalendareLangVariables()
            EventBus.$on('locale_change', () => {
                this.setCalendareLangVariables()
            });
        },
      created() {
          this.test = this.$i18n.locale
      },
      data() {
            return {
                showFiltersMenu: false,
                filterTime: false,
                filterWeekday: false,
                filterDate: false,
                filtersData: {
                    time: [],
                    weekday: [],
                    date: {
                        from: null,
                        to: null,
                    }
                },
                datePickerRuLang: ru,
            }
        },
        methods: {
            toggleFiltersMenu() {
                this.showFiltersMenu = !this.showFiltersMenu
            },
            morePosts() {
                this.$emit('more-posts')
            },
            upendPosts() {
                this.$emit('upend-posts')
            },
            filterPosts() {
                this.$emit('filter-posts', JSON.stringify(this.getFiltersData()))
            },
            getFiltersData() {
                return {
                    time: this.filterTime
                        ? this.filtersData.time
                        : null,
                    weekday: this.filterWeekday
                        ? this.filtersData.weekday
                        : null,
                    date: this.filterDate && (this.filtersData.date.from || this.filtersData.date.to)
                        ? this.filtersData.date
                        : null
                }
            },
          setCalendareLangVariables() {
              this.datePickerRuLang._monthsAbbr = this.$t('posts.dates.months')
              this.datePickerRuLang._days = [
                  this.$t('posts.dates.weekdays.7'),
                  this.$t('posts.dates.weekdays.1'),
                  this.$t('posts.dates.weekdays.2'),
                  this.$t('posts.dates.weekdays.3'),
                  this.$t('posts.dates.weekdays.4'),
                  this.$t('posts.dates.weekdays.5'),
                  this.$t('posts.dates.weekdays.6'),
              ]
          },
        },
        computed: {
            showMorePostsBtn() {
                return this.posts.length < this.posts_counter
            },
            postsCounter(){
              return Helpers.formatNumber(this.posts_counter,0, ' ', ' ')
            },
            likesCounter() {
              return Helpers.formatNumber(this.likes_counter, 0, ' ', ' ')
            },
            middleLikesPerPost: function () {
                return this.likes_counter === null || this.posts_counter === null
                    ? 0
                    : Number((this.likes_counter / this.posts_counter).toFixed(2))
            },
        },
    }
</script>

<style>
    .sort-third {
        padding: 0 !important;
    }

    #hours {
        width: 65px;
        background-color: #d8d6d6;
        border-radius: 5px;
        padding: 6px 8px;
        border: 1px solid hsl(0 0% 80% / 1);
        box-shadow: inset 0 1px 1px rgb(0 0 0 / 8%);
        color: #808080;
        -webkit-transition: border-color ease-in-out .15s,-webkit-box-shadow ease-in-out .15s;
        -o-transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;
        opacity: 1;
    }

    #hours:disabled {
      cursor: not-allowed;
    }

    #hours > option {
      color: #555;
    }

    #hours[disabled] > option {
      color: #808080;
    }

    #week-day {
        width: 135px;
        background: #d8d6d6;
        border-radius: 5px;
        padding: 6px 8px;
        border: 1px solid hsl(0 0% 80% / 1);
        box-shadow: inset 0 1px 1px rgb(0 0 0 / 8%);
        color: #808080;
        -webkit-transition: border-color ease-in-out .15s,-webkit-box-shadow ease-in-out .15s;
        -o-transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;
        opacity: 1;
    }

    #week-day:disabled {
      cursor: not-allowed;
    }

    #week-day > option {
      color: #555;
    }

    #week-day[disabled] > option {
      color: #808080;
    }

    .datetimepicker-from, .datetimepicker-to {
        width: 88px;
        padding: 6px 8px;
        background: #d8d6d6;
        font-size: 14px;
        border-radius: 5px;
        border: 1px solid hsl(0 0% 80% / 1);
        -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
        box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
        color: #555;
        position: relative;
        top: -5px;
        margin-bottom: 7px;
    }

    #hours > option {
      color: #555;
    }

    .datetimepicker-from:disabled,
    .datetimepicker-to:disabled {
        cursor: not-allowed;
        color: #808080;
    }

    .datetimepicker-from:focus,
    .datetimepicker-to:focus,
    #hours:focus,
    #week-day:focus
    {
        outline: none;
        border-color: #8e9698;
        -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
        box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
    }

    .datetimepicker-from::-webkit-input-placeholder,
    .datetimepicker-to::-webkit-input-placeholder {
      color: #555;
    }

    .datetimepicker-from::-moz-placeholder,
    .datetimepicker-to::-moz-placeholder {
      color: #555;
    }

    .datetimepicker-from:disabled::-webkit-input-placeholder,
    .datetimepicker-to:disabled::-webkit-input-placeholder {
        color: #808080;
    }

    .datetimepicker-from:disabled::-moz-placeholder,
    .datetimepicker-to:disabled::-moz-placeholder {
        color: #808080;
    }

    .mx-datepicker {
        width: 100px !important;
    }

    .extra-sort h4 {
        font-size: 14px;
    }

    .mx-icon-calendar {
        display: none;
    }

    .vdp-datepicker__calendar .cell.day-header {
        font-weight: 500;
        font-size: 14px !important;
    }

    .vdp-datepicker__calendar {
        position: absolute;
        z-index: 100;
        background: #fff;
        width: 300px;
        border-radius: 5px;
        border: 1px solid #c7c7c7;
        padding: 6px;
        color: black;
        -webkit-box-shadow: 0 1px 2px 0 rgba(0,0,0,.2);
        box-shadow: 0 1px 2px 0 rgba(0,0,0,.2);
    }

    .btns {
        margin-top: 0;
    }

    .additional-btn {
        padding: 3px 6px;
        background-color: #000;
        color: #fff;
        display: inline-block;
        border-radius: 5px;
        font-size: 12px;
        height: auto;
        margin-right: 2px;
        cursor: pointer;
    }

    .additional-btn:hover {
        background-color: hsl(0 0% 9% / 1);
    }

    .btn-filter {
    }

    .btn-upend {
    }

    option {
      font-size: 14px;
      font-family: 'Fira Sans', sans-serif;
    }
</style>