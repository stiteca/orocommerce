import FilteredProductVariantsPlugin from 'oroshoppinglist/js/datagrid/plugins/filtered-product-variants-plugin';

const isHighlight = item => item.isUpcoming || (item.errors && item.errors.length);
export const flattenData = data => {
    return data.reduce((flatData, rawData) => {
        const {subData, ...item} = rawData;
        const itemClassName = [];

        if (isHighlight(item)) {
            itemClassName.push('highlight');
        }

        if (!subData) {
            itemClassName.push('single-row');
            item.row_class_name = itemClassName.join(' ');
            flatData.push(item);
            item._hasVariants = false;
            item._isVariant = false;
        } else {
            let filteredOutVariants = 0;
            let lastFiltered = item;

            itemClassName.push('group-row');
            item.row_class_name = itemClassName.join(' ');
            item.ids = [];
            item._hasVariants = true;
            item._isVariant = false;
            flatData.push(item);
            subData.forEach((subItem, index) => {
                const className = ['sub-row'];

                if (subData.length - 1 === index) {
                    className.push('sub-row-last');
                }

                if (isHighlight(subItem)) {
                    className.push('highlight');
                }

                if (subItem.filteredOut) {
                    filteredOutVariants++;
                    className.push('hide');
                } else {
                    lastFiltered = subItem;
                }

                item.ids.push(subItem.id);
                subItem._isVariant = true;
                subItem.row_class_name = className.join(' ');
                subItem.row_attributes = {
                    'data-product-group': item.productId
                };
            });

            if (filteredOutVariants) {
                lastFiltered.filteredOutData = {
                    count: filteredOutVariants,
                    group: {
                        name: item.name,
                        id: item.productId
                    }
                };
            }

            flatData.push(...subData);
        }
        return flatData;
    }, []);
};

const shoppingListFlatDataBuilder = {
    processDatagridOptions(deferred, options) {
        Object.assign(options.metadata.options, {
            parseResponseModels: resp => {
                return 'data' in resp ? flattenData(resp.data) : resp;
            },
            parseResponseOptions: (resp = {}) => {
                const {options = {}} = resp;
                return {
                    reset: false,
                    uniqueOnly: true,
                    wait: false,
                    ...options
                };
            }
        });

        if (!options.metadata.plugins) {
            options.metadata.plugins = [];
        }
        options.metadata.plugins.push(FilteredProductVariantsPlugin);

        options.data.data = flattenData(options.data.data);

        deferred.resolve();
        return deferred;
    },

    /**
     * Init() function is required
     */
    init: deferred => deferred.resolve()
};

export default shoppingListFlatDataBuilder;
