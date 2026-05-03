//! Driip Rust Backend Library
//!
//! This library contains the shared modules used by both the main API server
//! and CLI utilities.

#![warn(unused)]
#![deny(clippy::all)]

pub mod auth;
pub mod config;
pub mod db;
pub mod domain;
pub mod errors;
pub mod health;
pub mod integrations;
pub mod middleware;
pub mod state;
