<!--

One rule (line) in the auto-category rules widget

Events:

- change: The rule has been changed

- delete: The user clicked the "delete" button and parent should delete this rule

-->
<template>
<li>
<pre>{{ JSON.stringify(rule)}}</pre>
<select name="feature" ref="feature" @change="onFeatureChange">
  <option></option>
  <!-- TODO: Make dynamic -->
  <option value="channel"  v-translate>Actu channel is</option>
  <option value="category" v-translate>Actu category is</option>
  <option value="theme"    v-translate>Has Actu theme</option>
  <option value="school"   v-translate>Actu in school</option>
  <option value="public"   v-translate>Targeted demographics has</option>
</select>
<select name="feature-value" ref="feature-value" class="feature-value">
</select>
<button @click.prevent="onDeleteButton"><font-awesome-icon icon="trash-alt" /></button>
</li>
</template>
<script>

import FontAwesomeIcon from '@fortawesome/vue-fontawesome'
import fontawesome from '@fortawesome/fontawesome'
// This is what it takes to make <font-awesome-icon icon="trash-alt" /> work, see
// https://github.com/FortAwesome/vue-fontawesome/issues/57#issuecomment-388545229
fontawesome.library.add(require('@fortawesome/fontawesome-free-solid/faTrashAlt'))

export default {
  components: {
    FontAwesomeIcon
  },
  props: {
    rule: Object,
  },
  methods: {
    onDeleteButton() {
      this.$emit("delete")
    },
    onFeatureChange() {
      console.log("onFeatureChange")
    }
  }
}
</script>
<style lang="scss" scoped>
li pre {
    display: inline-block;
}
select.feature-value {
    min-width: 80pt;
}
</style>
