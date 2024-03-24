import { createReduxStore, register } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const INITIAL_STATE = {
	settingsGroups: {},
	error: null,
	isSaving: false,
	isDirty: false
}

const actions = {
	setSettingsGroup( group, data, hydrate = false ) {
		return {
			type: 'SET_SETTINGS_GROUP',
			group,
			data,
			hydrate
		}
	},
	fetchSettingsGroup(group, args = {}) {
		return {
			type: 'FETCH',
			path: '/cp-locations/v1/settings/' + group,
			group,
			...args
		}
	},
	*persistSettingsGroup( group, data ) {
		yield { type: 'IS_SAVING', value: true }

		let response;
		try {
			response = yield actions.fetchSettingsGroup( group, { method: 'POST', data } );
		} catch ( e ) {
			return {
				type: 'SETTINGS_UPDATE_ERROR',
				message: e.message
			}
		}

		if ( response ) {
			return { type: 'SETTINGS_UPDATE_SUCCESS', data: response }
		}

		return { type: 'SETTINGS_UPDATE_ERROR', message: __( 'Settings were not saved.', 'cp-locations' ) }
	}
}

const settingsStore = createReduxStore( 'cp-locations/settings', {
	reducer: ( state = INITIAL_STATE, action ) => {
		switch ( action.type ) {
			case 'SET_SETTINGS_GROUP':
				return {
					...state,
					settingsGroups: {
						...state.settingsGroups,
						[ action.group ]: action.data
					},
					isDirty: action.hydrate ? false : true
				}
			case 'SETTINGS_UPDATE_SUCCESS':
				return {
					...state,
					error: null,
					isSaving: false,
					isDirty: false
				}
			case 'SETTINGS_UPDATE_ERROR':
				return {
					...state,
					error: action.message,
					isSaving: false
				}
			case 'IS_SAVING':
				return {
					...state,
					isSaving: action.value
				}
			default:
				return state;
		}
	},
	actions,
	selectors: {
		getSettingsGroup: ( state, group ) => {
			return state.settingsGroups[ group ];
		},
		isSaving: ( state ) => state.isSaving,
		isDirty: ( state ) => state.isDirty,
		getError: ( state ) => state.error
	},
	controls: {
		FETCH: ( args ) => apiFetch( args )
	},
	resolvers: {
		*getSettingsGroup( group ) {
			const response = yield actions.fetchSettingsGroup( group );
			return actions.setSettingsGroup( group, response, true );
		}
	}
} )

register( settingsStore )

export default settingsStore;
