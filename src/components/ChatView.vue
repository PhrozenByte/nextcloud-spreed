<!--
  - @copyright Copyright (c) 2019, Daniel Calviño Sánchez (danxuliu@gmail.com)
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div
		class="chatView"
		@dragover.prevent="handleDragOver"
		@dragleave.prevent="isDraggingOver = false"
		@drop.prevent="handleDropFiles">
		<transition name="slide" mode="out-in">
			<div
				v-show="isDraggingOver"
				class="dragover">
				<div class="drop-hint">
					<div
						class="drop-hint__icon"
						:class="{
							'icon-upload' : !isGuest && !isReadOnly,
							'icon-user' : isGuest,
							'icon-error' : isReadOnly}" />
					<h2
						class="drop-hint__text">
						{{ dropHintText }}
					</h2>
				</div>
			</div>
		</transition>
		<MessagesList
			role="region"
			:aria-label="t('spreed', 'Conversation messages')"
			:token="token" />
		<NewMessageForm
			role="region"
			:aria-label="t('spreed', 'Post message')" />
	</div>
</template>

<script>
import MessagesList from './MessagesList/MessagesList'
import NewMessageForm from './NewMessageForm/NewMessageForm'
import { processFiles } from '../utils/fileUpload'
import { CONVERSATION } from '../constants'

export default {

	name: 'ChatView',

	components: {
		MessagesList,
		NewMessageForm,
	},

	props: {
		token: {
			type: String,
			required: true,
		},
	},

	data: function() {
		return {
			isDraggingOver: false,
		}
	},

	computed: {
		isGuest() {
			return this.$store.getters.getActorType() === 'guests'
		},
		dropHintText() {
			if (this.isGuest) {
				return t('spreed', 'You need to be logged in to upload files')
			} else if (this.isReadOnly) {
				return t('spreed', 'This conversation is read-only')
			} else {
				return t('spreed', 'Drop your files to upload')
			}
		},
		isReadOnly() {
			if (this.$store.getters.conversation(this.token)) {
				return this.$store.getters.conversation(this.token).readOnly === CONVERSATION.STATE.READ_ONLY
			} else {
				return undefined
			}
		},
	},

	methods: {

		handleDragOver(event) {
			if (event.dataTransfer.types.includes('Files')) {
				this.isDraggingOver = true
			}
		},

		handleDropFiles(event) {
			if (!this.isDraggingOver) {
				return
			}

			// Restore non dragover state
			this.isDraggingOver = false
			// Stop the executin if the user is a guest
			if (this.isGuest || this.isReadOnly) {
				return
			}
			// Get the files from the event
			const files = Object.values(event.dataTransfer.files)
			// Create a unique id for the upload operation
			const uploadId = new Date().getTime()
			// Uploads and shares the files
			processFiles(files, this.token, uploadId)
		},
	},

}
</script>

<style lang="scss" scoped>
.chatView {
	width: 100%;
	height: 100%;
	display: flex;
	flex-direction: column;
	flex-grow: 1;
	position: absolute;
}

.dragover {
	position: absolute;
	top: 10%;
	left: 10%;
	width: 80%;
	height: 80%;
	background: var(--color-primary-light);
	z-index: 11;
	display: flex;
	box-shadow: 0px 0px 36px var(--color-box-shadow);
	border-radius: var(--border-radius);
	opacity: 90%;
}

.drop-hint {
	margin: auto;
	&__icon {
		background-size: 48px;
		height: 48px;
		margin-bottom: 16px;
	}
}

.slide {
	&-enter {
		transform: translateY(-50%);
		opacity: 0;
	}
	&-enter-to {
		transform: translateY(0);
		opacity: 1;
	}
	&-leave {
		transform: translateY(0);
		opacity: 1;
	}
	&-leave-to {
		transform: translateY(-50%);
		opacity: 0;
	}
	&-enter-active,
	&-leave-active {
		transition: all 150ms ease-in-out;
	}
}
</style>
