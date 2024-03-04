<!--
    The top-level component for the "auto-category rules" widget

    This lets the administrator create auto-category rules that work
    sort of like email filtering rules (with WordPress categories instead
    of folders).

    * Serializes/deserializes state to/from JSON

    * Serialized JSON is available as a hidden field for "traditional" form submit

    * Consumes a number of AJAX endpoints to enumerate categories, etc.

-->
<template>
<tr>
  <td colspan="2">
    <h1 v-translate>Auto-category rules</h1>
    <button @click.prevent="rules.push(newrule())">Add rule</button>
    <as-input-hidden :rules="rules" form-field="rules"></as-input-hidden>
    <!-- <input type="hidden" name="testing" value="1"> -->
    <ul>
      <li is="rule"
          v-for="rule in rules" :rule="rule" :key="rule.id"
          @delete="onDeleteRule(rule)"></li>
    </ul>
  </td>
</tr>
</template>
<script>
import _ from "lodash"

import AsInputHidden from "./as-input-hidden.vue"
import Rule          from "./rule.vue"

let ruleUnique = 0

export default {
  data: () => ({
    rules: [],
  }),
  components: {
    AsInputHidden,
    Rule
  },
  methods: {
    newrule: () => ({'foo': 'bar',
                     'id': ruleUnique ++ }),
    onDeleteRule(rule) {
      this.$set(this, "rules", _.filter(this.rules, (r) => (rule.id !== r.id)))
    }
  }
}
</script>
