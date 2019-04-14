<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolvers;

class InvertedListResolver implements \Vaimo\ComposerPatches\Interfaces\ListResolverInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;

    /**
     * @var \Vaimo\ComposerPatches\Interfaces\ListResolverInterface
     */
    private $baseResolver;

    /**
     * @param \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $baseResolver
     */
    public function __construct(
        \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $baseResolver
    ) {
        $this->baseResolver = $baseResolver;

        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }

    public function resolvePatchesQueue(array $patches)
    {
        $exclusions = $this->baseResolver->resolvePatchesQueue($patches);
        
        foreach ($exclusions as $target => $items) {
            $patches[$target] = array_diff_key($patches[$target], $items);
        }
        return $patches;
    }

    public function resolveRelevantPatches(array $patches, array $subset)
    {
        return $this->patchListUtils->intersectListsByPath($patches, $subset);
    }

    public function resolveInitialState(array $patches, array $state)
    {
        $patchesByTarget = $this->patchListUtils->groupItemsByTarget($patches);
        
        $unpackedState = $this->patchListUtils->createDetailedList($state);

        $updates = array();
        
        foreach ($patchesByTarget as $target => $group) {
            foreach ($group as $path => $item) {
                if (isset($unpackedState[$target][$path])) {
                    continue;
                }

                if (!isset($updates[$target])) {
                    $updates[$target] = array();
                }
                
                $updates[$target][$path] = $item;
            }
        }
        
        return $this->patchListUtils->mergeLists(
            $state,
            $this->patchListUtils->createSimplifiedList($updates)
        );
    }
}
