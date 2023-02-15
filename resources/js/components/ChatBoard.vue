<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Chard Board</div>

                    <div class="card-body">
                        <h3 v-if="chatWith"><em>{{ chatWith.name }}</em></h3>
                        <h3 v-else>Select a contact</h3>
                        <div class="conversation" ref="feed">
                            <ul class="message-list">
                                <li v-for="message in messages" :class="`message${message.to == user.id ? ' sent' : ' received'}`">
                                    <div class="text text-white">
                                        {{ message.text }}
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control" v-model="message.text" placeholder="type message..." @keydown="sendTypingEvent" @keyup.enter.prevent="sendMessage">
                            <span class="text-muted" v-if="activeUser">{{ activeUser }} is typing...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">Users</div>

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center" v-for="user in sortedUsers" @click.prevent="showMessage(user)" :class="{ 'active': user == chatWith }">
                            <span>
                                {{ user.name }} <br>
                                <small>{{ user.email }}</small>
                            </span>
                            <span class="badge badge-secondary badge-pill" v-if="user.unread">{{ user.unread }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['user'],
        data() {
            return {
                chatWith: null,
                users: [],
                messages: [],
                message: {
                    text: ''
                },
                activeUser: false,
                typingTimer: false
            }
        },
        computed: {
            sortedUsers() {
                return _.sortBy(this.users, [(user) => {
                    if (user == this.chatWith) {
                        return Infinity;
                    }

                    return user.unread;
                }]).reverse();
            }
        },
        mounted() {
            Echo.private(`message.${this.user.id}`)
                .listenForWhisper('typing', (response) => {
                    if (this.chatWith.id == response.id) {
                        this.activeUser = response.name;

                        if (this.typingTimer) {
                            clearTimeout(this.typingTimer);
                        }

                        this.typingTimer = setTimeout(() => {
                            this.activeUser = false;
                        }, 3000);
                    }
                })
                .listen('SendMessage', (e) => {
                    this.handleIncoming(e.message);
                });
            this.getUsers();
        },
        methods: {
            getUsers() {
                axios.get('users')
                    .then(response => {
                        this.users = response.data;
                        this.showMessage(response.data[0]);
                    });
            },
            showMessage(user) {
                this.updateUnreadCount(user, true);

                axios.get(`messages/${user.id}`)
                    .then(response => {
                        this.messages = response.data;
                        this.chatWith = user;
                        this.scrollToBottom();
                    });
            },
            sendMessage() {
                if (!this.chatWith) {
                    return;
                }

                axios.post('messages', {
                    user_id: this.chatWith.id,
                    text: this.message.text
                })
                    .then(response => {
                        this.message.text = '';
                        this.messages.push(response.data);
                        this.scrollToBottom();
                    });
            },
            handleIncoming(message) {
                if (this.chatWith && message.from == this.chatWith.id) {
                    this.messages.push(message);
                    this.scrollToBottom();
                    return;
                }
                this.updateUnreadCount(message.from_contact, false);
            },
            updateUnreadCount(user, reset) {
                this.users = this.users.map((single) => {
                    if (single.id !== user.id) {
                        return single;
                    }

                    if (reset)
                        single.unread = 0;
                    else
                        single.unread += 1;

                    return single;
                });
            },
            sendTypingEvent() {
                if (!this.chatWith) {
                    return;
                }

                Echo.private(`message.${this.chatWith.id}`)
                    .whisper('typing', this.user);
            },
            scrollToBottom() {
                setTimeout(() => {
                    this.$refs.feed.scrollTop = this.$refs.feed.scrollHeight - this.$refs.feed.clientHeight;
                }, 50);
            }
        }
    }
</script>

<style lang="scss" scoped>
    .conversation {
        height: 100%;
        max-height: 500px;
        overflow-y: scroll;
    }
    .list-group-item {
        cursor: pointer;
    }
    .message-list {
        list-style-type: none;
        padding: 5px;
        .message {
            margin: 10px 0;
            width: 100%;
            .text {
                max-width: 200px;
                border-radius: 5px;
                padding: 12px;
                display: inline-block;
            }
            &.sent {
                text-align: left;
                .text {
                    background-color: #6c757d;
                }
            }
            &.received {
                text-align: right;
                .text {
                    background-color: #6cb2eb;
                }
            }
        }
    }
</style>
