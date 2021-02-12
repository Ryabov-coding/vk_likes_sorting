<template>
	<div id="app">
    <div class="wrap-main">
      <LocaleSwitcher></LocaleSwitcher>
      <h2 class="main-header">
        <a v-html="$t('app-name')" href="/"></a>
      </h2>
      <div class="sub-header">
        {{ $t('subheader.first-part') }}
        <a href="https://vk.com">{{ $t('subheader.vkontakte') }}</a>
        <span v-html="$t('subheader.second-part')"></span>
      </div>
      <div class="window">
        <div>
          <label id="label-vk" for="vk-url"></label>
          <input v-model="searchPublicName" type="text" id="vk-url"
                 :placeholder="$t('input-placeholder')">
          <div v-if="errorText" class="no-js">
            <span>{{errorText}}</span>
          </div>
          <button v-on:click="start()" id="start"
                  class="btn btn-default">
            {{ $t('run-btn') }}
          </button>
        </div>
      </div>
      <img class="like" src="./assets/img/logo.png" alt="Эмблема ВК">
      <div v-if="showPublicDataLoading" class="loading">
        <div class="ajax-loader-wrapper">
          <img class="ajax-loader" src="./assets/img/heart-animation.png" alt="Загрузка">
          <div class="progress-wrapper">
            <progress v-if="showPublicDataLoadingProgressBar" id="progress"
                      class="progress" :value="progressBarPercent" max="1"></progress>
            <p id="loading-posts">
              {{formattedPostsCounter}}
            </p>
          </div>
        </div>
      </div>
    </div>

    <LastRequests v-if="!showPublicDataLoading && !showPublicFrame"
                  v-on:scan-public="scanPublic">
    </LastRequests>

    <PublicFrame v-if="showPublicFrame"
                 class="wrap-list"
                 ref="public_frame"
                 v-on:more-posts="morePosts"
                 v-on:upend-posts="upendPosts"
                 v-on:filter-posts="filterPosts"
                 :key="publicFrameKey"
                 :name="publicData.name"
                 :avatar_link="publicData.avatarLink"
                 :posts_counter="publicData.postsCounter"
                 :likes_counter="publicData.likesCounter"
                 :posts="publicData.posts"
                 :posts_per_page="postsPerPage">
    </PublicFrame>
	</div>
</template>

<script>
import PublicFrame from './components/PublicFrame'
import LastRequests from './components/LastRequests'
import LocaleSwitcher from './components/LocaleSwitcher'
import Helpers from './helpers'

export default {
	name: 'App',
	components: {
		PublicFrame,
		LastRequests,
		LocaleSwitcher
	},
  mounted() {
    let uri = window.location.href.split('?')

    if (uri.length === 2) {
      let vars = uri[1].split('&'),
          uriVars = {},
          tmp = ''

      vars.forEach((v) => {
        tmp = v.split('=');
        if (tmp.length === 2) {
          uriVars[tmp[0]] = tmp[1]
        }
      });

      if (uriVars.lang) {
        this.$i18n.locale = uriVars.lang
      }
    }

    document.title = this.$t('doc-title')
  },
  data () {
		return {
			searchPublicName: null,
			showPublicDataLoading: false,
			showPublicDataLoadingProgressBar: false,
			progressBarIntervalObject: null,
			progressBarPercent: 0,
			showPublicFrame: false,
			publicFrameKey: 0,
			publicData: {
				id: null,
				name: null,
				postsCounter: null,
				likesCounter: null,
				posts: null,
				avatarLink: null,
			},
			postsPerPage: 100,
			errorText: null,
			startBtnBlocked: false,
		}
	},
	methods: {
		scanPublic(publicLink) {
			this.searchPublicName = publicLink
			this.start()
		},
		start() {
			let rus_letters_regexp = /[а-яё]/i,
				eng_letters_regexp = /[a-z]/i,
				right_urls = [
					'www.vk.com',
					'vk.com',
					'm.vk.com',
					'www.m.vk.com',
					'www.vkontakte.ru',
					'www.vkontakte.com'
				],
				right_protocols = [
					'http:',
					'https:'
				]


			this.errorText = null

			if (!this.searchPublicName || this.searchPublicName === '') {
				this.errorText = 'Введите название или ссылку'
			} else {
				var searchPublicName = this.searchPublicName.trim()
				let url_parts = searchPublicName.split('/'),
					url_length = url_parts.length

				// Если это ссылку, разбираем её, проверяя, что она корректна и достаем из неё название паблика
				if (url_length > 1) {
					if (rus_letters_regexp.test(searchPublicName) ||
						(url_length === 2 && !right_urls.includes(url_parts[0])) ||
						(url_length === 3 && !right_urls.includes(url_parts[1])) ||
						(url_length === 4 && (!right_protocols.includes(url_parts[0]) || !right_urls.includes(url_parts[2]) || url_parts[1] !== ''))
					) {
						this.errorText = 'Неверная ссылка'
					} else {
						searchPublicName = url_parts[url_length - 1]
					}
				}

				if (searchPublicName.indexOf('?') !== -1) {
					searchPublicName = searchPublicName.split('?')[0];
				}
				if (searchPublicName && !eng_letters_regexp.test(searchPublicName)) {
					searchPublicName = 'public' + searchPublicName;
				}
			}

			if (this.errorText) {
				return false
			}

			this.showPublicFrame = false
			this.showPublicDataLoadingProgressBar = false
			this.showPublicDataLoading = true
			this.publicData.id = null
			this.publicData.postsCounter = null

			this.getPublicInfo(searchPublicName)
				.then((resp) => {
					if (resp.success) {
						let data = resp.data
						this.publicData.id = data.public_id
						this.publicData.postsCounter = data.posts_counter
						this.showPublicDataLoadingProgressBar = true

						this.launchProgressBar(this.publicData.postsCounter)
						this.getPosts()
					} else {
						if (resp.error !== null) {
							this.errorText = resp.error
							this.showPublicFrame = false
							this.showPublicDataLoadingProgressBar = false
							this.showPublicDataLoading = false
						}
					}
				})
		},
		async getPublicInfo(searchPublicName) {
			return await this.$http.get('/app.php', {
				before(request) {
					if (this.previousRequest) {
						this.previousRequest.abort();
					}
					this.previousRequest = request;
				},
				params: {
					action: 'get_public_info',
					action_params: {
						public_name: searchPublicName,
					}
				},
			}).then(response => {
				return response.body
			});
		},
		getPosts() {
			this.$http.get('/app.php', {
				before(request) {
					if (this.previousRequest) {
						this.previousRequest.abort();
					}
					this.previousRequest = request;
				},
				params: {
					action: 'get_posts',
					action_params: {
						public_id: this.publicData.id,
					}
				},
			}).then(response => {
				this.showPublicDataLoading = false

				if (response.body.success) {
					let data = response.body.data

					if (data.posts.length) {
						this.publicData.name = data.name
						this.publicData.postsCounter = data.posts_counter
						this.publicData.likesCounter = data.likes_counter
						this.publicData.posts = data.posts
						this.publicData.avatarLink = data.avatar_link
						this.showPublicFrame = true
					}
				} else {
					if (response.body.error !== null) {
						this.errorText = response.body.error
						this.showPublicFrame = false
						this.showPublicDataLoadingProgressBar = false
					}
				}
				// Обновление компонента
				this.publicFrameKey += 1
				this.stopProgressBar()
			});
		},
		morePosts() {
			this.$http.get('/app.php', {
				params: {
					action: 'more_posts',
					action_params: {
						public_id: this.publicData.id,
						offset: this.publicData.posts.length,
						filters: this.getFiltersData(),
					}
				},
			}).then(response => {
				if (response.body.success) {
					let data = response.body.data
					Array.prototype.push.apply(this.publicData.posts, data.posts)
					// Обновление компонента
					this.publicFrameKey += 1
				}
			});
		},
		upendPosts() {
			this.$http.get('/app.php', {
				params: {
					action: 'upend_posts',
					action_params: {
						public_id: this.publicData.id,
						filters: this.getFiltersData(),
					}
				},
			}).then(response => {
				if (response.body.success) {
					let data = response.body.data
					this.publicData.posts = data.posts
				}
			});
		},
		filterPosts(filters) {
			this.$http.get('/app.php', {
				before(request) {
					if (this.previousRequest) {
						this.previousRequest.abort();
					}
					this.previousRequest = request;
				},
				params: {
					action: 'filter_posts',
					action_params: {
						public_id: this.publicData.id,
						filters: filters,
					}
				},
			}).then(response => {
				if (response.body.success) {
					let data = response.body.data
					this.publicData.postsCounter = data.posts_counter
					this.publicData.likesCounter = data.likes_counter
					this.publicData.posts = data.posts
				}
			});
		},
		launchProgressBar(posts_num) {
			this.progressBarPercent = 0
			this.progressBarIntervalObject = setInterval(() => {
				this.progressBarPercent = this.progressBarPercent >= 1
					? 0.75
					: this.progressBarPercent += 0.01
			}, this.postsLoadSpeedCounter(posts_num));
		},
		stopProgressBar() {
			this.progressBarIntervalObject = 0
		},
		getFiltersData() {
			return this.$refs.public_frame.getFiltersData()
		},
		postsLoadSpeedCounter(postsCounter) {
			return (Math.pow(postsCounter, 1/2) * 3) + 100;
		},
		blockStartBtn() {
			this.startBtnBlocked = true
		},
		unblockStartBtn() {
			this.startBtnBlocked = false
		}
	},
	computed: {
		formattedPostsCounter: function ()  {
			return this.publicData.postsCounter
				? Helpers.formatNumber(this.publicData.postsCounter, 0, ' ', ' ')
				: null
		},
	},
}
</script>

<style>
body {
	/*background-color: #2F343B;*/
	/*background-color: #fffcef;*/
	/*background-color: #f4fffb;*/
	/*background-color: hsl(158 40% 95% / 1);*/
	background-color: hsl(235deg 61% 55%);
	font-family: 'Fira Sans', sans-serif;
	font-size: 16px;
}

.wrap-main {
	position: relative;
	margin: 0 auto;
	width: 500px;
	margin-top: 24.5vh;
}

h2 {
	font-family: Fira Sans,sans-serif;
	font-weight: 700;
	font-size: 64px;
	color: #ffffff;
	z-index: 5;
	text-transform: uppercase;
	margin-bottom: -2px!important;
  line-height: 0.87;
}

.sub-header {
  margin: 20px 0;
  font-weight: 500;
  font-size: 19px;
  color: #ffffff;
  padding-right: 100px;
  line-height: 1.3;
  width: 385px;
}

.sub-header a {
	color: #ffffff;
	text-decoration: underline;
	/*border-bottom: 1px solid white;*/
	/*padding-bottom: 0;*/
}

.sub-header a:hover {
	color: #d8d8d8;
}

form {
	margin-top: 12px;
}

.like {
	position: absolute;
	z-index: 1;
  top: -94px;
  left: 347px;
  height: 275px;
}

input {
	font-family: 'Fira Sans', sans-serif;
	font-weight: 400;
}

label {
	color: #dff0e4;
	font-family: 'Fira Sans', sans-serif;
	font-style: italic;
	font-weight: 400;
}

form button {
	margin-top: -2px;
	height: 40px !important;
	width: 130px !important;
	font-size: 13px !important;
}

form button:active {
	-webkit-transition: all 0.5s ease;
	-moz-transition: all 1s ease;
	-ms-transition: all 1s ease;
	-o-transition: all 1s ease;
	transition: all 1s ease;
}

#label-vk {
	color: #b94641;
	margin-bottom: 9px;
}

#vk-url {
	background-color: #ffffff;
	border-radius: 8px;
	height: 45px;
	margin-top: 8px;
	color: black;
	font-size: 19px;
   width: 485px;
	margin-bottom: -5px;
	border: none;
	padding: 0 0 0 15px;
}

#vk-url:focus {
	background-color: #ffffff;
	color: black;
	font-size: 19px;
	-webkit-box-shadow: none;
	box-shadow: none;
  outline: none;
}

#vk-url:-webkit-autofill,
#vk-url:-webkit-autofill:hover,
#vk-url:-webkit-autofill:focus,
#vk-url:-webkit-autofill:active  {
	transition: background-color 10000s ease-in-out 0s;
	font-size: 19px !important;
}

#start {
  font-family: 'Fira Sans', sans-serif;
	background-color: #000000;
	width: 102px!important;
	height: 43px!important;
	margin-top: 16px;
	font-size: 15px!important;
	font-weight: 500;
	color: #fff!important;
	border: none;
	border-radius: 8px;
	display: block;
  cursor: pointer;
}

#start:hover {
	background-color: hsl(0 0% 9% / 1);
}

#start:focus {
	outline: none;
}

#vk-url::-webkit-input-placeholder {
	color: #000000;
	font-family: 'Fira Sans', sans-serif !important;
}

#vk-url::-moz-placeholder {
	color: #000000;
	font-family: 'Fira Sans', sans-serif !important;
}

#vk-url:-ms-input-placeholder {
	color: #000000;
	font-family: 'Fira Sans', sans-serif !important;
}

#vk-url:-moz-placeholder {
	color: #000000;
	font-family: 'Fira Sans', sans-serif !important;
}

.wrong {
	border: 1px solid #b94641 !important;
}

h6 {
	color: black;
	opacity: .3;
	margin-top: 350px;
	font-size: 15px;
}

.plus {
	position: relative;
	top: 2px;
}

@keyframes pulsation {
	0%   {height: 59px;}
	10%  {height: 48px;}
	20%  {height: 56px;}
	30% {height: 43px;}
	40% {height: 59px;}
	50% {height: 48px;}
	60% {height: 56px;}
	70% {height: 43px;}
	80% {height: 59px;}
	90% {height: 48px;}
	100% {height: 59px;}
}

.ajax-loader {
	height: 60px;
	opacity: .9;
	display: block;
	border: none;
	margin: 0 auto;
	animation: pulsation 5s infinite;
}

.loading {
	position: relative;
	margin-top: 36px;
}

.progress-wrapper {
	position: absolute;
	top: 58px;
	left: -10px;
}

.progress {
	background-color: white;
	height: 5px;
	width: 125px;
	color: black;
	margin-top: 0;
	margin-bottom: 2px !important;
}

.loading p {
	color: white;
	font-size: 12px;
	text-align: center;
}

.ajax-loader-wrapper {
	position: relative;
	margin: 0 auto;
	width: 20%;
}

.progress[value] {
	border: none;
	background-color: white;
}

.progress:not([value]) {
	background-color: white;
}

.progress[value]::-webkit-progress-value {
	background-color: white;
}

.progress[value]::-moz-progress-bar {
	background-color: white;
}

progress[value]::-webkit-progress-bar {
	background-color: black;
}

.main-header a {
	cursor: pointer;
	text-decoration: none;
	color: white;
}

.main-header a:hover {
	text-decoration: none;
	color: white;
}

.lang-selector {
	font-size: 19px;
	color: white;
	font-weight: 700;
	position: absolute;
	top: 30px;
}





.wrap-list {
  background-color: #ffffff;
  margin: 35px auto 50px auto;
  width: 500px;
  padding: 10px 0 20px 0;
  -webkit-transition: all 0.2s ease;
  -moz-transition: all 0.2s ease;
  -ms-transition: all 0.2s ease;
  -o-transition: all 0.2s ease;
  transition: all 0.2s ease;
  border-radius: 8px;
  /*-webkit-box-shadow: 0 1px 2px 0 rgba(0,0,0,.14);*/
  /*box-shadow: 0 1px 2px 0 rgba(0,0,0,.14);*/
}

.list .mini-like {
  height: 20px;
}

.info {
  margin-left: 31px;
  width: 86%;
  color: #2F343B;
  margin-bottom: 18px;
  line-height: 16.5px;
}

.info text {
  display: inline-block;
}

.info h3 {
  font-size: 28px;
  width: 250px;
  margin-top: 15px;
  line-height: 1;
  margin-bottom: 14px;
  font-weight: 700;
  color:black
}

.info h4 {
  width: 250px;
  font-size: 15px;
  margin-bottom: 4px;
  margin-top: 4px;
  color:black
}

.info img {
  display: inline-block;
  float: right;
  height: 138px;
  border: 1px solid rgba(51, 55, 69, .14);
  margin-top: 2px;
  position: relative;
  right: -8px;
}

.like-count {
  color: black;
  text-align: left;
  margin-left: 15px;
  font-weight: 500;
}

.like-count:before {
  content: url('./assets/img/like.png');
  position: relative;
  top: 2px;
  right: 5px;
}

.list a {
  color: black;
  font-size: 15px;
  margin-left: 10px;
  text-decoration: underline;
  position: relative;
  left: 3px;
}

.list a:hover {
  color: #43433a;
}

.list .visited {
  color: #828278;
}

.list p {
  font-size: 15px;
  display: inline-block;
}

.list {
  width: 97%;
  margin: 0 auto;
  top: 5%;
  margin-top: 20px;
}

table {
  width: 90%;
  line-height: 1;
  margin: 0 auto;
  color:black !important;
  border-collapse: collapse;
}

.first-row {
  min-width: 30px;
  opacity: .45;
  font-size: 12px;
}

.second-row {
}

.third-row {
}

.forth-row {
}

.fivth-row {
}

.seven-row {
}

.list tr {
  border-bottom: 1px solid rgba(51,55,69, .15);
  font-size: 15px !important;
}

.list td {
  padding-top: 4px;
  height: 40px !important;
}

.no-js {
  background-color: hsl(341deg 93% 61%);
  padding: 5px 10px;
  color: #ffffff;
  font-size: 12px;
  margin-left: 0;
  margin-top: 12px;
  display: inline-block;
  font-weight: 500;
  margin-bottom: 0;
  line-height: 1;
  border-radius: 5px;
}

#upend {
  cursor: pointer;
  font-size: 15px;
}

/*=======================*/
/* Фильтрация списка
/*=======================*/
.extra-sort {
  width: 90%;
  margin-left: 32px;
  margin-bottom: 10px;
}

.calendar {
  height: 30px;
}

.list .last-tr {
  border: none;
}

.extra-sort .filter-input {
  width: 75% !important;
  display: block;
  height: 35px !important;
}

.types {
  margin-top: 20px;
  background-color: hsl(0 0% 94% / 1);
  width: 97%;
  border-radius: 8px;
}

.types table {
  margin-left: 15px;
  width: 100%;
}

.test {
  padding: 10px 15px 20px 15px;
}

.test th {
  text-align: left;
}

.test th input {
  margin-left: 0;
  margin-right: 4px;
}

.table-row {
  display: table-row;
}

.types .filter-checkbox {
  display: inline-block;
}

.types h4 {
  display: inline-block;
  margin-top: 5px;
  color: #2F343B !important;
  margin-bottom: 10px;
}

.filter-checkbox {
  width: 17px;
  height: 17px;
  position: relative;
  top: 3px;
  cursor: pointer;
}

.list:after {
  content: '';
  background-color: #ffffff;
  display: block;
  width: 100%;
  height: 10px;
  position: relative;
  top: -2px;
  z-index: 5;
}

.sort-first {
  width: 20% !important;
}

.types td {
  width: 30%;
  margin-right: 80px;
}

.filter-active {
  border: 1px solid #86a2ab !important;
}

.filter-input {
  display: inline !important;
  margin-left: 2px;
  padding-left: 9px;
}

#sort {
  font-family: 'Fira Sans', sans-serif;
  margin-top: 17px;
  background-color: rgb(0 0 0);
  border-radius: 5px;
  color: white;
  font-size: 12px;
  padding: 5px 9px;
  margin-left: 14px;
  border: none;
  cursor: pointer;
}

#sort:hover {
  background-color: hsl(0 0% 9% / 1);
}

.more {
  font-size: 12px;
  text-align: center;
  padding: 12px 0;
  width: 87.3%;
  margin: 2px auto 10px auto;
  cursor: pointer;
  background-color: black;
  border-radius: 8px;
  color: white;
  font-weight: 500;
}

.more:hover {
  background-color: hsl(0 0% 9% / 1);
}

.filter-hour {
  width: 104px;
}

#check-date {
  margin-left: 24px;
}

.filter-date-wrapper {
  margin-left: 23px;
}
</style>
