// SPDX-License-Identifier: MIT
// Reference ERC-5192 Soulbound Token contract (documentation only)
// Deploy using Hardhat/Foundry with this source as a reference.
pragma solidity ^0.8.20;

import "@openzeppelin/contracts/token/ERC721/ERC721.sol";
import "@openzeppelin/contracts/token/ERC721/extensions/ERC721URIStorage.sol";
import "@openzeppelin/contracts/access/Ownable.sol";

/**
 * @title SoulboundToken
 * @dev ERC-5192 Minimum Soulbound Token — non-transferable NFT.
 *      Implements IERC5192 locked() returning true for all tokens.
 */
contract SoulboundToken is ERC721, ERC721URIStorage, Ownable {
    uint256 private _nextTokenId;

    event Locked(uint256 tokenId);
    event Unlocked(uint256 tokenId);

    constructor(
        string memory name,
        string memory symbol,
        string memory /* baseUri — reserved for future use */
    ) ERC721(name, symbol) Ownable(msg.sender) {}

    /**
     * @dev Mint a new soulbound token to `to` with metadata `uri`.
     *      Only the contract owner (FinAegis backend signer) may mint.
     */
    function safeMint(address to, string memory uri) public onlyOwner {
        uint256 tokenId = _nextTokenId++;
        _safeMint(to, tokenId);
        _setTokenURI(tokenId, uri);
        emit Locked(tokenId);
    }

    /**
     * @dev Burn (revoke) a soulbound token. Only the owner may burn.
     */
    function burn(uint256 tokenId) public onlyOwner {
        _burn(tokenId);
    }

    /**
     * @dev ERC-5192: All tokens are permanently locked (soulbound).
     */
    function locked(uint256 /* tokenId */) external pure returns (bool) {
        return true;
    }

    // --- Transfer blocking overrides ---

    function transferFrom(address, address, uint256) public pure override(ERC721, IERC721) {
        revert("SoulboundToken: transfer is not allowed");
    }

    function safeTransferFrom(address, address, uint256, bytes memory) public pure override(ERC721, IERC721) {
        revert("SoulboundToken: transfer is not allowed");
    }

    // --- Required overrides ---

    function tokenURI(uint256 tokenId) public view override(ERC721, ERC721URIStorage) returns (string memory) {
        return super.tokenURI(tokenId);
    }

    function supportsInterface(bytes4 interfaceId) public view override(ERC721, ERC721URIStorage) returns (bool) {
        return super.supportsInterface(interfaceId);
    }
}
