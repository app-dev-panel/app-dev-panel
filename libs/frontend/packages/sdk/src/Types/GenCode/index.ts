export type GenCodeGeneratorAttributeRule = {0: string; [name: string]: any};

export type GenCodeGeneratorAttribute = {
    defaultValue: string | number | null | string[];
    hint: string | null;
    label: string | null;
    rules: GenCodeGeneratorAttributeRule[];
};
