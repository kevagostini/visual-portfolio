/**
 * External dependencies
 */
import { debounce } from 'throttle-debounce';
import Select, { components } from 'react-select';
import AsyncSelect from 'react-select/async';
import CreatableSelect from 'react-select/creatable';

const {
    Option,
} = components;

/**
 * WordPress dependencies
 */
const {
    jQuery: $,
    ajaxurl,
    VPGutenbergVariables,
} = window;

const {
    __,
} = wp.i18n;

const {
    Component,
} = wp.element;

/**
 * Internal dependencies
 */
const customStyles = {
    control: ( styles, state ) => ( {
        ...styles,
        cursor: 'pointer',
        minHeight: 30,
        ...state.isFocused && ! state.isDisabled ? {
            borderColor: '#007cba',
        } : {
            borderColor: '#7e8993',
            '&:hover': {
                borderColor: '#7e8993',
            },
        },
        ...state.isFocused ? {
            boxShadow: '0 0 0 1px #007cba',
            '&:hover': {
                borderColor: '#007cba',
            },
        } : {},
    } ),
    valueContainer: ( styles ) => ( {
        ...styles,
        padding: '0 8px',
    } ),
    input: ( styles ) => ( {
        ...styles,
        margin: 0,
        height: 30,
        paddingTop: 0,
        paddingBottom: 0,
        input: {
            boxShadow: 'none !important',
        },
    } ),
    dropdownIndicator: ( styles ) => ( {
        ...styles,
        padding: 5,
        color: '#555555',
        '&:hover': {
            color: '#555555',
        },
        svg: {
            width: 17,
            height: 17,
        },
    } ),
    clearIndicator: ( styles ) => ( {
        ...styles,
        padding: 5,
        svg: {
            width: 15,
            height: 15,
        },
    } ),
    indicatorSeparator: () => false,
    multiValue: ( styles ) => ( {
        ...styles,
        padding: '2px 3px',
        borderRadius: 4,
        backgroundColor: '#555d66',
    } ),
    multiValueLabel: ( styles ) => ( {
        ...styles,
        padding: 0,
        color: '#fff',
    } ),
    multiValueRemove: ( styles ) => ( {
        ...styles,
        padding: 0,
        marginLeft: 5,
        color: '#adadad',
        svg: {
            width: 10,
            height: 10,
        },
    } ),
    option: ( styles, state ) => ( {
        ...styles,
        display: 'flex',
        alignItems: 'center',
        padding: '4px 10px',
        ...state.isFocused ? {
            backgroundColor: '#efefef',
        } : {},
        ...state.isSelected ? {
            backgroundColor: '#007cba',
        } : {},
    } ),
    menu: ( styles ) => ( {
        ...styles,
        zIndex: 2,
    } ),
};

const cachedOptions = {};

/**
 * Component Class
 */
export default class VpfSelectControl extends Component {
    constructor( ...args ) {
        super( ...args );

        const {
            callback,
        } = this.props;

        this.state = {
            options: {},
            ajaxStatus: !! callback,
        };

        this.getOptions = this.getOptions.bind( this );
        this.getDefaultValue = this.getDefaultValue.bind( this );
        this.findValueData = this.findValueData.bind( this );
        this.requestAjax = this.requestAjax.bind( this );
        this.requestAjaxDebounce = debounce( 300, this.requestAjax );
    }

    componentDidMount() {
        const {
            callback,
        } = this.props;

        if ( callback ) {
            this.requestAjax( {}, ( result ) => {
                if ( result.options ) {
                    this.setState( {
                        options: result.options,
                    } );
                }
            } );
        }
    }

    /**
     * Get options list.
     *
     * @returns {Object} - options list for React Select.
     */
    getOptions() {
        const {
            controlName,
        } = this.props;

        if ( cachedOptions[ controlName ] ) {
            return cachedOptions[ controlName ];
        }

        return Object.keys( this.state.options ).length ? this.state.options : this.props.options;
    }

    /**
     * Get default value in to support React Select attribute.
     *
     * @returns {Object} - value object for React Select.
     */
    getDefaultValue() {
        const {
            value,
            isMultiple,
        } = this.props;

        let result = null;

        if ( isMultiple ) {
            if ( ( ! value && 'string' !== typeof value ) || ! value.length ) {
                return result;
            }

            result = [];

            value.forEach( ( innerVal ) => {
                result.push( this.findValueData( innerVal ) );
            } );
        } else {
            if ( ! value && 'string' !== typeof value ) {
                return result;
            }

            result = this.findValueData( value );
        }

        return result;
    }

    /**
     * Find option data by value.
     *
     * @param {String} findVal - value.
     *
     * @returns {Object|Boolean} - value object.
     */
    findValueData( findVal ) {
        let result = {
            value: findVal,
            label: findVal,
        };

        const options = this.getOptions();

        // Find value in options.
        if ( options ) {
            Object.keys( options ).forEach( ( val ) => {
                const data = options[ val ];

                if ( val === findVal ) {
                    if ( 'string' === typeof data ) {
                        result.label = data;
                    } else {
                        result = data;
                    }
                }
            } );
        }

        return result;
    }

    /**
     * Request AJAX dynamic data.
     *
     * @param {Object} additionalData - additional data for AJAX call.
     * @param {Function} callback - callback.
     * @param {Boolean} useStateLoading - use state change when loading.
     */
    requestAjax( additionalData = {}, callback, useStateLoading = true ) {
        const {
            controlName,
            attributes,
        } = this.props;

        if ( this.isAJAXinProgress ) {
            return;
        }

        this.isAJAXinProgress = true;

        if ( useStateLoading ) {
            this.setState( {
                ajaxStatus: 'progress',
            } );
        }

        const ajaxData = {
            action: 'vp_dynamic_control_callback',
            nonce: VPGutenbergVariables.nonce,
            vp_control_name: controlName,
            vp_attributes: attributes,
            ...additionalData,
        };

        $.ajax( {
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: ajaxData,
            complete: ( data ) => {
                const json = data.responseJSON;

                if ( callback && json.response ) {
                    if ( json.response.options ) {
                        cachedOptions[ controlName ] = {
                            ...cachedOptions[ controlName ],
                            ...json.response.options,
                        };
                    }

                    callback( json.response );
                }

                if ( useStateLoading ) {
                    this.setState( {
                        ajaxStatus: true,
                    } );
                }

                this.isAJAXinProgress = false;
            },
        } );
    }

    /**
     * Prepare options for React Select structure.
     *
     * @param {Object} options - options object.
     *
     * @returns {Object} - prepared options.
     */
    // eslint-disable-next-line class-methods-use-this
    prepareOptions( options ) {
        return Object.keys( options || {} ).map( ( val ) => {
            const option = options[ val ];

            if ( 'object' === typeof option ) {
                return {
                    value: option.value,
                    label: option.label,
                };
            }

            return {
                value: val,
                label: options[ val ],
            };
        } );
    }

    render() {
        const {
            onChange,
            isMultiple,
            isSearchable,
            isCreatable,
            callback,
        } = this.props;

        const {
            ajaxStatus,
        } = this.state;

        const isAsync = !! callback && isSearchable;
        const isLoading = ajaxStatus && 'progress' === ajaxStatus;

        const selectProps = {
            // Test opened menu items:
            // menuIsOpen: true,
            className: 'vpf-component-select',
            styles: customStyles,
            components: {
                Option( optionProps ) {
                    const {
                        data,
                    } = optionProps;

                    return (
                        <Option { ...optionProps }>
                            { 'undefined' !== typeof data.img ? (
                                <div className="vpf-component-select-option-img">
                                    { data.img ? (
                                        <img src={ data.img } alt={ data.label } />
                                    ) : '' }
                                </div>
                            ) : '' }
                            <span className="vpf-component-select-option-label">
                                { data.label }
                            </span>
                            { data.category ? (
                                <div className="vpf-component-select-option-category">
                                    { data.category }
                                </div>
                            ) : '' }
                        </Option>
                    );
                },
            },
            value: this.getDefaultValue(),
            options: this.prepareOptions( this.getOptions() ),
            onChange( val ) {
                if ( isMultiple ) {
                    if ( Array.isArray( val ) ) {
                        const result = [];

                        val.forEach( ( innerVal ) => {
                            result.push( innerVal ? innerVal.value : '' );
                        } );

                        onChange( result );
                    } else {
                        onChange( [] );
                    }
                } else {
                    onChange( val ? val.value : '' );
                }
            },
            isMulti: isMultiple,
            isSearchable,
            isLoading,
            isClearable: false,
            placeholder: isSearchable ? __( 'Type to search...', '@@text_domain' ) : __( 'Select...', '@@text_domain' ),
        };

        // Creatable select.
        if ( isCreatable ) {
            selectProps.placeholder = __( 'Type and press Enter...', '@@text_domain' );
            selectProps.isSearchable = true;

            return (
                <CreatableSelect
                    { ...selectProps }
                />
            );
        }

        // Async select.
        if ( isAsync ) {
            selectProps.loadOptions = ( inputValue, cb ) => {
                this.requestAjaxDebounce(
                    { q: inputValue },
                    ( result ) => {
                        const newOptions = [];

                        if ( result && result.options ) {
                            Object.keys( result.options ).forEach( ( k ) => {
                                newOptions.push( result.options[ k ] );
                            } );
                        }

                        cb( newOptions.length ? newOptions : null );
                    },
                    false
                );
            };
            selectProps.cacheOptions = true;
            selectProps.defaultOptions = selectProps.options;

            delete selectProps.options;
            delete selectProps.isLoading;

            return (
                <AsyncSelect
                    { ...selectProps }
                />
            );
        }

        // Default select.
        return (
            <Select
                { ...selectProps }
            />
        );
    }
}