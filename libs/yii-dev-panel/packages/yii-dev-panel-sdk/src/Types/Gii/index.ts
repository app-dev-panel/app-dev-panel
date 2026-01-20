export type GiiGeneratorAttributeRule = {
    0: string;
    [name: string]: any;
};

export type GiiGeneratorAttribute = {
    defaultValue: string | number | null | string[];
    hint: string | null;
    label: string | null;
    rules: GiiGeneratorAttributeRule[];
};
