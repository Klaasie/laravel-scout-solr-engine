const dateMask = IMask(
    document.querySelector('input[name="publication_date"]'),
    {
        mask: Date,
        pattern: 'Y-m-d',
        blocks: {
            d: {
                mask: IMask.MaskedRange,
                from: 1,
                to: 31,
                maxLength: 2,
            },
            m: {
                mask: IMask.MaskedRange,
                from: 1,
                to: 12,
                maxLength: 2,
            },
            Y: {
                mask: IMask.MaskedRange,
                from: 1000,
                to: 9999,
            }
        },
        format: function (date) {
            let day = date.getDate();
            let month = date.getMonth() + 1;
            const year = date.getFullYear();

            if (day < 10) day = "0" + day;
            if (month < 10) month = "0" + month;

            return [year, month, day].join('-');
        },
        parse: function (str) {
            const yearMonthDay = str.split('-');
            return new Date(yearMonthDay[0], yearMonthDay[1] - 1, yearMonthDay[2]);
        },
        autofix: true,
        lazy: true,
        overwrite: true
    }
);
