export enum FileOperationEnum {
    SAVE = 'save',
    SKIP = 'skip',
}

export enum FileStateEnum {
    PRESENT_SAME = 'present_same',
    PRESENT_DIFFERENT = 'present_different',
    NOT_EXIST = 'not_exist',
}

export type GiiFile = {
    content: string;
    id: string;
    operation: FileOperationEnum;
    path: string;
    preview: string;
    relativePath: string;
    state: FileStateEnum;
    type: string;
};
