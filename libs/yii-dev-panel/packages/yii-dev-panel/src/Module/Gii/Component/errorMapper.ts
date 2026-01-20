import {UseFormReturn} from 'react-hook-form';

export function mapErrorsToForm(response: any, form: UseFormReturn) {
    if ('error' in response) {
        const errorsMap = (response.error?.data?.errors as Record<string, string[]>) || {};
        console.error(errorsMap);

        for (const attribute in errorsMap) {
            const errors = errorsMap[attribute];
            form.setError(attribute, {message: errors.join(' ')});
        }
    }
}
