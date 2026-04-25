// redux-persist/lib/storage is CJS and breaks under Vite 8 (rolldown)
// with "storage.getItem is not a function". Inline the same localStorage
// wrapper that redux-persist/lib/storage provides.
const storage = {
    getItem: (key: string) => Promise.resolve(localStorage.getItem(key)),
    setItem: (key: string, item: string) => Promise.resolve(localStorage.setItem(key, item)),
    removeItem: (key: string) => Promise.resolve(localStorage.removeItem(key)),
};

export default storage;
