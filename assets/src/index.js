/**
 * Kjeks AI Reviewer — registers an "AI Reviewer" tab into the Kjeks network
 * admin screen via the `kjeks.networkAdminTabs` filter.
 */
import { createElement as h, useState, useEffect, useMemo, useCallback } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import {
	Button,
	Notice,
	Spinner,
	SelectControl,
	CheckboxControl,
	ToggleControl,
	Card,
	CardBody,
	Flex,
	FlexItem,
} from '@wordpress/components';

import './admin.css';

const settings = window.kjeksAiReviewer || { restBase: '', nonce: '' };

if ( settings.nonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( settings.nonce ) );
}

const CATEGORIES = [
	{ label: __( 'Necessary', 'kjeks-ai-reviewer' ), value: 'necessary' },
	{ label: __( 'Preferences', 'kjeks-ai-reviewer' ), value: 'preferences' },
	{ label: __( 'Analytics', 'kjeks-ai-reviewer' ), value: 'analytics' },
	{ label: __( 'Marketing', 'kjeks-ai-reviewer' ), value: 'marketing' },
];

function request( path, options = {} ) {
	return apiFetch( { url: settings.restBase + path, ...options } );
}

function SuggestionRow( { item, category, selected, onSelect, onCategoryChange, onAccept, onReject, busy } ) {
	const suggestion = item.suggestion;
	const confidence = suggestion ? Math.round( suggestion.confidence * 100 ) : 0;

	return h(
		'tr',
		{},
		h(
			'td',
			{ className: 'check-column' },
			suggestion
				? h( CheckboxControl, {
						__nextHasNoMarginBottom: true,
						checked: selected,
						onChange: ( on ) => onSelect( item.id, on ),
						'aria-label': item.name || item.id,
				  } )
				: null
		),
		h( 'td', {}, h( 'strong', {}, item.name ), item.domain ? h( 'div', { className: 'kjeks-ai__muted' }, item.domain ) : null ),
		h(
			'td',
			{},
			suggestion
				? h(
						'div',
						{},
						h( SelectControl, {
							__nextHasNoMarginBottom: true,
							value: category,
							options: CATEGORIES,
							onChange: ( value ) => onCategoryChange( item.id, value ),
						} ),
						h( 'div', { className: 'kjeks-ai__muted' }, sprintf( __( '%d%% confidence', 'kjeks-ai-reviewer' ), confidence ) )
				  )
				: h( 'em', { className: 'kjeks-ai__muted' }, __( 'No suggestion yet', 'kjeks-ai-reviewer' ) )
		),
		h(
			'td',
			{},
			suggestion
				? h(
						'div',
						{},
						suggestion.provider ? h( 'div', {}, suggestion.provider ) : null,
						suggestion.rationale ? h( 'div', { className: 'kjeks-ai__muted' }, suggestion.rationale ) : null,
						suggestion.grounded_by
							? h( 'span', { className: 'kjeks-ai__badge' }, __( 'Grounded', 'kjeks-ai-reviewer' ) )
							: null
				  )
				: null
		),
		h(
			'td',
			{},
			suggestion
				? h( CheckboxControl, {
						__nextHasNoMarginBottom: true,
						checked: false,
						disabled: busy,
						'aria-label': sprintf( __( 'Accept suggestion for %s', 'kjeks-ai-reviewer' ), item.name || item.id ),
						onChange: ( on ) => {
							if ( on ) {
								onAccept( item.id, category );
							}
						},
				  } )
				: null
		),
		h(
			'td',
			{ className: 'kjeks-ai__actions' },
			suggestion
				? h(
						Button,
						{
							variant: 'link',
							size: 'small',
							isDestructive: true,
							disabled: busy,
							onClick: () => onReject( item.id ),
						},
						__( 'Reject', 'kjeks-ai-reviewer' )
				  )
				: null
		)
	);
}

function ReviewerApp() {
	const [ state, setState ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ busy, setBusy ] = useState( false );
	const [ notice, setNotice ] = useState( null );
	const [ selected, setSelected ] = useState( () => new Set() );
	const [ categories, setCategories ] = useState( {} );

	const load = useCallback( () => {
		setLoading( true );
		request( '/state' )
			.then( ( data ) => setState( data ) )
			.catch( ( e ) => setNotice( { type: 'error', text: e.message } ) )
			.finally( () => setLoading( false ) );
	}, [] );

	useEffect( () => {
		load();
	}, [ load ] );

	// Seed the per-row category from each suggestion, keeping any admin edits,
	// and drop selections for rows that are no longer pending.
	useEffect( () => {
		if ( ! state ) {
			return;
		}
		setCategories( ( prev ) => {
			const next = { ...prev };
			state.pending.forEach( ( item ) => {
				if ( item.suggestion && next[ item.id ] === undefined ) {
					next[ item.id ] = item.suggestion.category;
				}
			} );
			return next;
		} );
		setSelected( ( prev ) => {
			const ids = new Set( state.pending.map( ( item ) => item.id ) );
			const next = new Set();
			prev.forEach( ( id ) => ids.has( id ) && next.add( id ) );
			return next;
		} );
	}, [ state ] );

	const pending = state ? state.pending : [];

	const pendingById = useMemo( () => {
		const map = {};
		pending.forEach( ( item ) => ( map[ item.id ] = item ) );
		return map;
	}, [ pending ] );

	const selectableIds = useMemo(
		() => pending.filter( ( item ) => item.suggestion ).map( ( item ) => item.id ),
		[ pending ]
	);

	const categoryFor = useCallback(
		( item ) => {
			const value = categories[ item.id ];
			if ( value !== undefined ) {
				return value;
			}
			return item.suggestion ? item.suggestion.category : 'marketing';
		},
		[ categories ]
	);

	const setCategory = ( id, value ) => setCategories( ( prev ) => ( { ...prev, [ id ]: value } ) );

	const toggleSelected = ( id, on ) =>
		setSelected( ( prev ) => {
			const next = new Set( prev );
			if ( on ) {
				next.add( id );
			} else {
				next.delete( id );
			}
			return next;
		} );

	const selectAll = ( on ) =>
		setSelected( ( prev ) => {
			const next = new Set( prev );
			selectableIds.forEach( ( id ) => ( on ? next.add( id ) : next.delete( id ) ) );
			return next;
		} );

	const generate = ( force ) => {
		setBusy( true );
		setNotice( null );
		request( '/suggest', { method: 'POST', data: { force } } )
			.then( ( data ) => {
				setState( data );
				if ( data.result ) {
					const errors = Object.keys( data.result.errors || {} ).length;
					setNotice( {
						type: errors ? 'warning' : 'success',
						text: sprintf(
							/* translators: 1: suggested count, 2: processed count, 3: error count */
							__( 'Suggested %1$d of %2$d processed (%3$d skipped).', 'kjeks-ai-reviewer' ),
							data.result.suggested,
							data.result.processed,
							errors
						),
					} );
				}
			} )
			.catch( ( e ) => setNotice( { type: 'error', text: e.message } ) )
			.finally( () => setBusy( false ) );
	};

	const accept = ( id, category ) => {
		setBusy( true );
		request( '/accept', { method: 'POST', data: { id, category } } )
			.then( () => {
				setNotice( { type: 'success', text: __( 'Suggestion accepted and review recorded.', 'kjeks-ai-reviewer' ) } );
				load();
			} )
			.catch( ( e ) => setNotice( { type: 'error', text: e.message } ) )
			.finally( () => setBusy( false ) );
	};

	const reject = ( id ) => {
		setBusy( true );
		request( '/reject', { method: 'POST', data: { id } } )
			.then( () => load() )
			.catch( ( e ) => setNotice( { type: 'error', text: e.message } ) )
			.finally( () => setBusy( false ) );
	};

	const acceptSelected = () => {
		const targets = Array.from( selected )
			.map( ( id ) => pendingById[ id ] )
			.filter( ( item ) => item && item.suggestion );
		// `necessary` is accepted one item at a time, so it is skipped in bulk.
		const acceptable = targets.filter( ( item ) => categoryFor( item ) !== 'necessary' );
		const skipped = targets.length - acceptable.length;

		if ( ! acceptable.length ) {
			setNotice( {
				type: 'warning',
				text: __( 'Necessary suggestions must be accepted one at a time.', 'kjeks-ai-reviewer' ),
			} );
			return;
		}

		setBusy( true );
		Promise.all(
			acceptable.map( ( item ) => request( '/accept', { method: 'POST', data: { id: item.id, category: categoryFor( item ) } } ) )
		)
			.then( () => {
				setNotice( {
					type: skipped ? 'warning' : 'success',
					text: skipped
						? sprintf(
								/* translators: 1: accepted count, 2: skipped necessary count */
								__( 'Accepted %1$d suggestions; %2$d necessary skipped (accept those individually).', 'kjeks-ai-reviewer' ),
								acceptable.length,
								skipped
						  )
						: sprintf( __( 'Accepted %d suggestions.', 'kjeks-ai-reviewer' ), acceptable.length ),
				} );
				load();
			} )
			.catch( ( e ) => setNotice( { type: 'error', text: e.message } ) )
			.finally( () => setBusy( false ) );
	};

	const rejectSelected = () => {
		const ids = Array.from( selected ).filter( ( id ) => pendingById[ id ] );
		if ( ! ids.length ) {
			return;
		}
		setBusy( true );
		Promise.all( ids.map( ( id ) => request( '/reject', { method: 'POST', data: { id } } ) ) )
			.then( () => {
				setNotice( { type: 'success', text: sprintf( __( 'Rejected %d suggestions.', 'kjeks-ai-reviewer' ), ids.length ) } );
				load();
			} )
			.catch( ( e ) => setNotice( { type: 'error', text: e.message } ) )
			.finally( () => setBusy( false ) );
	};

	const toggleCron = ( value ) => {
		request( '/settings', { method: 'POST', data: { cron_enabled: value } } )
			.then( ( data ) => setState( data ) )
			.catch( ( e ) => setNotice( { type: 'error', text: e.message } ) );
	};

	if ( loading && ! state ) {
		return h( 'div', { className: 'kjeks-ai' }, h( Spinner ) );
	}

	if ( state && ! state.supported ) {
		return h(
			'div',
			{ className: 'kjeks-ai' },
			h(
				Notice,
				{ status: 'warning', isDismissible: false },
				__( 'This site does not support the WordPress AI client, so suggestions are unavailable.', 'kjeks-ai-reviewer' )
			)
		);
	}

	const allSelected = selectableIds.length > 0 && selectableIds.every( ( id ) => selected.has( id ) );

	return h(
		'div',
		{ className: 'kjeks-ai' },
		notice
			? h( Notice, { status: notice.type, onRemove: () => setNotice( null ) }, notice.text )
			: null,
		h(
			Card,
			{},
			h(
				CardBody,
				{},
				h(
					Flex,
					{ align: 'center', justify: 'space-between', wrap: true },
					h(
						FlexItem,
						{},
						h( 'p', {}, sprintf( __( '%d unreviewed cookies pending.', 'kjeks-ai-reviewer' ), state ? state.pendingCount : 0 ) )
					),
					h(
						FlexItem,
						{},
						h(
							Button,
							{ variant: 'primary', disabled: busy, onClick: () => generate( false ) },
							busy ? __( 'Working…', 'kjeks-ai-reviewer' ) : __( 'Generate suggestions', 'kjeks-ai-reviewer' )
						)
					)
				),
				h( ToggleControl, {
					__nextHasNoMarginBottom: true,
					label: __( 'Generate suggestions automatically once a week', 'kjeks-ai-reviewer' ),
					checked: state ? state.cronEnabled : false,
					onChange: toggleCron,
				} )
			)
		),
		selected.size > 0
			? h(
					'div',
					{ className: 'kjeks-ai__bulk' },
					h(
						'span',
						{},
						sprintf(
							/* translators: %d: number of selected suggestions. */
							__( '%d selected', 'kjeks-ai-reviewer' ),
							selected.size
						)
					),
					h(
						Button,
						{ variant: 'primary', disabled: busy, onClick: acceptSelected },
						__( 'Accept selected', 'kjeks-ai-reviewer' )
					),
					h(
						Button,
						{ variant: 'secondary', disabled: busy, isDestructive: true, onClick: rejectSelected },
						__( 'Reject selected', 'kjeks-ai-reviewer' )
					),
					h(
						Button,
						{ variant: 'tertiary', disabled: busy, onClick: () => setSelected( new Set() ) },
						__( 'Clear', 'kjeks-ai-reviewer' )
					)
			  )
			: null,
		pending.length
			? h(
					'table',
					{ className: 'widefat striped kjeks-ai__table' },
					h(
						'thead',
						{},
						h(
							'tr',
							{},
							h(
								'td',
								{ className: 'check-column' },
								h( CheckboxControl, {
									__nextHasNoMarginBottom: true,
									checked: allSelected,
									disabled: selectableIds.length === 0,
									onChange: selectAll,
									'aria-label': __( 'Select all', 'kjeks-ai-reviewer' ),
								} )
							),
							h( 'th', {}, __( 'Cookie', 'kjeks-ai-reviewer' ) ),
							h( 'th', {}, __( 'Suggested category', 'kjeks-ai-reviewer' ) ),
							h( 'th', {}, __( 'Details', 'kjeks-ai-reviewer' ) ),
							h( 'th', {}, __( 'Reviewed', 'kjeks-ai-reviewer' ) ),
							h( 'th', {}, __( 'Actions', 'kjeks-ai-reviewer' ) )
						)
					),
					h(
						'tbody',
						{},
						pending.map( ( item ) =>
							h( SuggestionRow, {
								key: item.id,
								item,
								category: categoryFor( item ),
								selected: selected.has( item.id ),
								onSelect: toggleSelected,
								onCategoryChange: setCategory,
								onAccept: accept,
								onReject: reject,
								busy,
							} )
						)
					)
			  )
			: h( 'p', {}, __( 'Nothing pending — every cookie has been reviewed.', 'kjeks-ai-reviewer' ) )
	);
}

addFilter( 'kjeks.networkAdminTabs', 'kjeks-ai-reviewer', ( tabs ) => {
	return [
		...tabs,
		{
			name: 'ai-reviewer',
			title: __( 'AI Reviewer', 'kjeks-ai-reviewer' ),
			render: () => h( ReviewerApp ),
		},
	];
} );
