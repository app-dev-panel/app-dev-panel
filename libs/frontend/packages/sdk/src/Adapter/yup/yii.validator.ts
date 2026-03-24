import {yup} from '@app-dev-panel/sdk/Adapter/yup';
import {GenCodeGeneratorAttribute, GenCodeGeneratorAttributeRule} from '@app-dev-panel/sdk/Types/GenCode';
import * as Yup from 'yup';
import {Schema} from 'yup';

function createYupValidationRules(rules: GenCodeGeneratorAttributeRule[]) {
    const currentSet: Schema[] = [];

    for (const rule of rules) {
        switch (rule[0]) {
            case 'required':
                currentSet.push(yup.string().required(rule.message));
                break;
            case 'each':
                currentSet.push(yup.array(createYupValidationRules(rule.rules)) as any);
                break;
            case 'regex':
                /*eslint no-case-declarations: "off"*/
                const originalPattern = rule.pattern as string;
                const lastSlashPosition = originalPattern.lastIndexOf('/');

                const flags = originalPattern.slice(lastSlashPosition + 1);
                const regex = originalPattern.slice(0, lastSlashPosition - originalPattern.length).slice(1);
                // console.log(
                //     'orig', originalPattern,
                //     'new', regex,
                //     'flags', flags
                // )
                currentSet.push(yup.string().matches(new RegExp(regex, flags), {message: rule.message.message}));

                break;
        }
    }

    return yup.mixed().sequence(currentSet);
}

export function createYupValidationSchema(attributes: Record<string, GenCodeGeneratorAttribute>): Yup.AnyObjectSchema {
    const rulesSet: any = {};
    Object.entries(attributes).map(([attributeName, attribute], index) => {
        rulesSet[attributeName] = createYupValidationRules(attribute.rules);
    });

    return yup.object(rulesSet) as Yup.AnyObjectSchema;
}
