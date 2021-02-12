<template>
	<div class="recent-requests-wrapper">
		<div class="wrap-list">
			<div class="recent-requests">
				<h4>{{ $t('last-calls-title') }}</h4>
				<table>
					<tr v-for="(recentRequest, index) in recentRequestsList"
              v-bind:key="index">
						<td width="2%" class="first-row">{{++index}}</td>
						<td width="50%">
							<a class="public-link"
							   target="_blank"
							   :href="recentRequest.link">
								{{recentRequest.title}}
							</a>
						</td>
						<td>{{handleRequestSpendTime(recentRequest.spend_time)}}</td>
						<td>
							<span v-on:click="scanPublic(recentRequest.link)"
								  class="run-btn">
								{{ $t('run-btn') }}
							</span>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
</template>

<script>
    export default {
      name: 'LastRequests',
      props: [
      ],
      created() {
        this.getLastRequests()
      },
      computed: {
      },
      data() {
        return {
          recentRequestsList: [],
        }
      },
      methods: {
        async getLastRequests() {
          return await this.$http.get('/app.php', {
            params: {
              action: 'get_last_requests',
            },
          }).then(response => {
            this.recentRequestsList = response.body.data
          });
        },
        scanPublic(publicLink) {
          this.$emit('scan-public', publicLink)
        },
        handleRequestSpendTime(timeDate) {
          let min = this.$t('last-requests.min'),
              sec = this.$t('last-requests.sec'),
              result = ''

          if (timeDate.min > 0) {
            result += timeDate.min + ' ' + min + ' '
          }

          return result + timeDate.sec + ' ' + sec
        }
      },
    }
</script>

<style>
.recent-requests-wrapper .wrap-list {
	margin-top: 35px;
}

.recent-requests {
	padding: 0 31px;
}

.recent-requests h4 {
	font-size: 15px;
	color: black;
	font-weight: 700;
  margin: 12px 0;
}

.recent-requests table {
	width: 100%;
}

.recent-requests tr {
	height: 40px;
	font-size: 15px;
  border-bottom: 1px solid rgba(51,55,69, .15);
}

.recent-requests tr:last-child {
	border-bottom: none;
}

.recent-requests tr td:last-child {
	text-align: right;
}

.recent-requests .public-link {
	color: black;
	font-size: 15px;
	text-decoration: underline;
}

.recent-requests .public-link:hover {
	color: #43433a;
}

.recent-requests .run-btn {
	font-weight: 700;
	cursor: pointer;
	text-align: right;
}

.recent-requests .run-btn:hover {
	color: #43433a;
}
</style>
