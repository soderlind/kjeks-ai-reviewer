/**
 * Kjeks AI Reviewer — registers an "AI Reviewer" tab into the Kjeks network
 * admin screen via the `kjeks.networkAdminTabs` filter.
 */
import { createElement as h, useState, useEffect, useCallback } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import {
	Button,
	Notice,
	Spinner,
	SelectControl,
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

const HIGH_CONFIDENCE = 0.8;

function request( path, options = {} ) {
	return apiFetch( { url: settings.restBase + path, ...options } );
}

function SuggestionRow( { item, onAccept, onReject, busy } ) {
	const suggestion = item.suggestion;
	const [ category, setCategory ] = useState( suggestion ? suggestion.category : 'marketing' );

	const confidence = suggestion ? Math.round( suggestion.confidence * 100 ) : 0;

	return h(
		'tr',
		{},
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
							onChange: setCategory,
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
			{ className: 'kjeks-ai__actions' },
			suggestion
				? h(
						'div',
						{},
						h(
							Button,
							{
								variant: 'primary',
								size: 'small',
								disabled: busy,
								onClick: () => onAccept( item.id, category ),
							},
							__( 'Accept', 'kjeks-ai-reviewer' )
						),
						h(
							Button,
							{
								variant: 'tertiary',
								size: 'small',
								isDestructive: true,
								disabled: busy,
								onClick: () => onReject( item.id ),
							},
							__( 'Reject', 'kjeks-ai-reviewer' )
						)
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

	const acceptHighConfidence = () => {
		if ( ! state ) {
			return;
		}
		const targets = state.pending.filter(
			( i ) => i.suggestion && i.suggestion.category !== 'necessary' && i.suggestion.confidence >= HIGH_CONFIDENCE
		);
		if ( ! targets.length ) {
			setNotice( { type: 'info', text: __( 'No high-confidence, non-necessary suggestions to accept.', 'kjeks-ai-reviewer' ) } );
			return;
		}
		setBusy( true );
		Promise.all(
			targets.map( ( i ) => request( '/accept', { method: 'POST', data: { id: i.id, category: i.suggestion.category } } ) )
		)
			.then( () => {
				setNotice( {
					type: 'success',
					text: sprintf( __( 'Accepted %d high-confidence suggestions.', 'kjeks-ai-reviewer' ), targets.length ),
				} );
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

	const pending = state ? state.pending : [];

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
						),
						h(
							Button,
							{ variant: 'secondary', disabled: busy, onClick: acceptHighConfidence, style: { marginLeft: '8px' } },
							__( 'Accept high-confidence', 'kjeks-ai-reviewer' )
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
							h( 'th', {}, __( 'Cookie', 'kjeks-ai-reviewer' ) ),
							h( 'th', {}, __( 'Suggested category', 'kjeks-ai-reviewer' ) ),
							h( 'th', {}, __( 'Details', 'kjeks-ai-reviewer' ) ),
							h( 'th', {}, __( 'Actions', 'kjeks-ai-reviewer' ) )
						)
					),
					h(
						'tbody',
						{},
						pending.map( ( item ) =>
							h( SuggestionRow, { key: item.id, item, onAccept: accept, onReject: reject, busy } )
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
