<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = withDefaults(
    defineProps<{
        modelValue?: string;
        name: string;
        autocomplete?: string;
        showStrength?: boolean;
        placeholder?: string;
    }>(),
    {
        modelValue: '',
        autocomplete: 'current-password',
        showStrength: false,
        placeholder: '',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const { t } = useI18n();
const visible = ref(false);

const strength = computed(() => Math.min(100, props.modelValue.length * 10));

function onInput(event: Event): void {
    emit('update:modelValue', (event.target as HTMLInputElement).value);
}
</script>

<template>
    <div>
        <input
            :autocomplete="autocomplete"
            class="form-control"
            :name="name"
            :placeholder="placeholder"
            :type="visible ? 'text' : 'password'"
            :value="modelValue"
            @input="onInput"
        />
        <p class="nova-row nova-muted" style="margin-top: 8px">
            <button class="nova-btn nova-btn-link" type="button" @click="visible = !visible">
                {{ t('auth.togglePassword') }}
            </button>
        </p>
        <div v-if="showStrength" class="nova-password-bar" aria-hidden="true">
            <b :style="{ width: `${strength}%` }" />
        </div>
        <p v-if="showStrength" class="nova-muted">{{ t('auth.passwordStrength') }}</p>
    </div>
</template>
