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
        inlineToggle?: boolean;
        id?: string;
    }>(),
    {
        modelValue: '',
        autocomplete: 'current-password',
        showStrength: false,
        placeholder: '',
        inlineToggle: false,
        id: undefined,
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
        <div v-if="inlineToggle" class="nova-password-field">
            <input
                :id="id"
                :autocomplete="autocomplete"
                class="form-control"
                :name="name"
                :placeholder="placeholder"
                :type="visible ? 'text' : 'password'"
                :value="modelValue"
                @input="onInput"
            />
            <button
                class="nova-password-toggle"
                type="button"
                :aria-label="t('auth.togglePassword')"
                @click="visible = !visible"
            >
                <svg v-if="!visible" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path
                        d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"
                        stroke="currentColor"
                        stroke-width="1.75"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.75" />
                </svg>
                <svg v-else viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path
                        d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M7.9 7.9C6.2 9.1 4.8 10.8 4 12c0 0 3.5 7 10 7 2 0 3.8-.5 5.3-1.4M14.1 5.2C13.1 5.1 12 5 11 5 4.5 5 1 12 1 12"
                        stroke="currentColor"
                        stroke-width="1.75"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </button>
        </div>
        <template v-else>
            <input
                :id="id"
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
        </template>
        <div v-if="showStrength" class="nova-password-bar" aria-hidden="true">
            <b :style="{ width: `${strength}%` }" />
        </div>
        <p v-if="showStrength" class="nova-muted">{{ t('auth.passwordStrength') }}</p>
    </div>
</template>
