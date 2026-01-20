export const nl2br = (string: string) => {
    return string.split('\n').map(function (item, key) {
        return (
            <span key={key}>
                {item}
                <br />
            </span>
        );
    });
};
