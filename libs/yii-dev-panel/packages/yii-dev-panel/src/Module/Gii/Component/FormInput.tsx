import {Autocomplete, FormHelperText, TextField} from '@mui/material';
import {matchInputType} from '@yiisoft/yii-dev-panel-sdk/Adapter/yii/inputTypeMatcher';
import {GiiGeneratorAttribute} from '@yiisoft/yii-dev-panel-sdk/Types/Gii';
import {Controller, useFormContext} from 'react-hook-form';

type FormInputProps = {
    attributeName: string;
    attribute: GiiGeneratorAttribute;
};

export function FormInput({attributeName, attribute}: FormInputProps) {
    const form = useFormContext();
    const type = matchInputType(attribute.rules.map((rule) => rule[0]));

    if (type === 'text') {
        return (
            <Controller
                name={attributeName}
                // rules={rules}
                defaultValue={String(attribute.defaultValue ?? '')}
                control={form.control}
                render={({field, fieldState: {error}}) => (
                    <>
                        <TextField
                            {...field}
                            placeholder={String(attribute.defaultValue ?? '')}
                            label={attribute.label || attributeName}
                            error={!!error}
                            helperText={error ? error.message : null}
                        />
                        {!!attribute.hint && <FormHelperText>{attribute.hint}</FormHelperText>}
                    </>
                )}
            />
        );
    }

    if (type === 'select') {
        return (
            <Controller
                control={form.control}
                // rules={rules}
                defaultValue={Array.isArray(attribute.defaultValue) ? attribute.defaultValue : []}
                name={attributeName}
                render={({field: {value, onChange, onBlur, ref}, fieldState: {error}}) => (
                    <>
                        <Autocomplete
                            value={value}
                            onChange={(_, items) => onChange(items)}
                            multiple
                            filterSelectedOptions
                            filterOptions={(v) => v}
                            freeSolo={true}
                            options={[]}
                            renderInput={(params) => (
                                <TextField
                                    {...params}
                                    ref={ref}
                                    onBlur={() => {
                                        onBlur();
                                    }}
                                    name={attributeName}
                                    helperText={error ? error.message : null}
                                    error={!!error}
                                    label={attribute.label}
                                />
                            )}
                        />
                        <FormHelperText>{attribute.hint}</FormHelperText>
                    </>
                )}
            />
        );
    }
    return null;
}
