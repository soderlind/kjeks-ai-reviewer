<?php
/**
 * Minimal stand-ins for the Kjeks classes the reviewer integrates with, so the
 * add-on's logic can be unit-tested without the Kjeks plugin present.
 *
 * These mirror only the surface the add-on actually calls.
 *
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

namespace Soderlind\Kjeks\Inventory;

if ( ! class_exists( __NAMESPACE__ . '\\Tracker' ) ) {
	final class Tracker {

		public const PARTY_FIRST = 'first';
		public const PARTY_THIRD = 'third';

		public function __construct(
			public readonly string $id,
			public readonly string $name,
			public readonly string $category = 'marketing',
			public readonly bool $reviewed = false,
			public readonly string $provider = '',
			public readonly string $purpose = '',
			public readonly string $party = self::PARTY_THIRD,
			public readonly string $storage_type = 'cookie',
			public readonly string $domain = '',
			public readonly string $path = '/',
			public readonly string $retention = '',
			public readonly string $source = '',
			public readonly string $documentation_url = '',
			public readonly int $first_observed = 0,
			public readonly int $last_observed = 0,
			public readonly array $sites = array(),
		) {}

		/**
		 * @param array<string, mixed> $data Raw data.
		 */
		public static function from_array( array $data ): self {
			return new self(
				id: (string) ( $data['id'] ?? '' ),
				name: (string) ( $data['name'] ?? '' ),
				category: (string) ( $data['category'] ?? 'marketing' ),
				reviewed: ! empty( $data['reviewed'] ),
				provider: (string) ( $data['provider'] ?? '' ),
				purpose: (string) ( $data['purpose'] ?? '' ),
				party: (string) ( $data['party'] ?? self::PARTY_THIRD ),
				storage_type: (string) ( $data['storage_type'] ?? 'cookie' ),
				domain: (string) ( $data['domain'] ?? '' ),
				path: (string) ( $data['path'] ?? '/' ),
				retention: (string) ( $data['retention'] ?? '' ),
				source: (string) ( $data['source'] ?? '' ),
				documentation_url: (string) ( $data['documentation_url'] ?? '' ),
				first_observed: (int) ( $data['first_observed'] ?? 0 ),
				last_observed: (int) ( $data['last_observed'] ?? 0 ),
				sites: (array) ( $data['sites'] ?? array() ),
			);
		}

		/**
		 * @return array<string, mixed>
		 */
		public function to_array(): array {
			return array(
				'id'                => $this->id,
				'name'              => $this->name,
				'category'          => $this->category,
				'reviewed'          => $this->reviewed,
				'provider'          => $this->provider,
				'purpose'           => $this->purpose,
				'party'             => $this->party,
				'storage_type'      => $this->storage_type,
				'domain'            => $this->domain,
				'path'              => $this->path,
				'retention'         => $this->retention,
				'source'            => $this->source,
				'documentation_url' => $this->documentation_url,
				'first_observed'    => $this->first_observed,
				'last_observed'     => $this->last_observed,
				'sites'             => $this->sites,
			);
		}

		public function with_review( string $category ): self {
			$data             = $this->to_array();
			$data['category'] = $category;
			$data['reviewed'] = true;

			return self::from_array( $data );
		}
	}
}
