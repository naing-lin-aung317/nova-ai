import type { ComposerTranslation } from 'vue-i18n';

const KNOWN_FAILURE_CODES = [
    'region_restricted',
    'missing_api_key',
    'invalid_api_key',
    'missing_model',
    'unsupported_model',
    'provider_error',
    'generation_failed',
    'timeout',
    'stopped',
] as const;

type KnownFailureCode = (typeof KNOWN_FAILURE_CODES)[number];

const FAILURE_I18N_KEY: Record<KnownFailureCode, string> = {
    region_restricted: 'generator.errors.regionRestricted',
    missing_api_key: 'generator.errors.missingApiKey',
    invalid_api_key: 'generator.errors.invalidApiKey',
    missing_model: 'generator.errors.missingModel',
    unsupported_model: 'generator.errors.unsupportedModel',
    provider_error: 'generator.errors.providerError',
    generation_failed: 'generator.errors.generationFailed',
    timeout: 'generator.errors.timeout',
    stopped: 'generator.stopped',
};

export function isKnownFailureCode(reason: string | null | undefined): reason is KnownFailureCode {
    return KNOWN_FAILURE_CODES.includes(reason as KnownFailureCode);
}

export function generationFailureMessage(
    reason: string | null | undefined,
    t: ComposerTranslation,
): string {
    if (! reason) {
        return t('generator.failedDefault');
    }

    if (isKnownFailureCode(reason)) {
        return t(FAILURE_I18N_KEY[reason]);
    }

    return reason;
}